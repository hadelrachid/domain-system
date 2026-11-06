<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Storage\LocalPublicStorage;
use DomainSystem\Plugins\academy\Contracts\CourseRepositoryInterface;
use DomainSystem\Plugins\academy\Contracts\CourseStructureRepositoryInterface;
use DomainSystem\Plugins\academy\Controllers\LessonAdminController;
use DomainSystem\Plugins\academy\Services\LessonBlockRenderer;
use DomainSystem\Plugins\academy\Services\LocalLessonPdfStorage;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AcademyPdfViewerBlockTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/ds_pdf_test_' . uniqid('', true);
        @mkdir($this->tempDir . '/public', 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->removeDir($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    public function testLocalLessonPdfStorageValidatesMagicHeaderAndStoresValidPdf(): void
    {
        $storage = new LocalPublicStorage($this->tempDir, fn() => 'https://meusite.com');
        $pdfStorage = new LocalLessonPdfStorage($storage);

        $validPdfPath = $this->tempDir . '/sample.pdf';
        file_put_contents($validPdfPath, "%PDF-1.7\n1 0 obj << /Type /Catalog >> endobj\n%%EOF");

        $result = $pdfStorage->storePdfUpload([
            'name'     => 'Apostila Cap 3 - Gráficos.pdf',
            'tmp_name' => $validPdfPath,
            'error'    => UPLOAD_ERR_OK,
            'size'     => filesize($validPdfPath),
        ]);

        $this->assertStringStartsWith('https://meusite.com/uploads/academy/pdfs/pdf_apostila-cap-3-gr-ficos_', $result['url']);
        $this->assertStringEndsWith('.pdf', $result['file_name']);
        $this->assertGreaterThan(10, $result['size']);
    }

    public function testLocalLessonPdfStorageRejectsDisguisedNonPdfFile(): void
    {
        $storage = new LocalPublicStorage($this->tempDir, fn() => 'https://meusite.com');
        $pdfStorage = new LocalLessonPdfStorage($storage);

        $fakePdfPath = $this->tempDir . '/evil.pdf';
        file_put_contents($fakePdfPath, "<script>alert('xss')</script>");

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('%PDF-');

        $pdfStorage->storePdfUpload([
            'name'     => 'evil.pdf',
            'tmp_name' => $fakePdfPath,
            'error'    => UPLOAD_ERR_OK,
            'size'     => filesize($fakePdfPath),
        ]);
    }

    public function testLessonBlockRendererRendersPdfCanvasModeWithPageSliceAndDownloadLock(): void
    {
        $storage = new LocalPublicStorage($this->tempDir, fn() => 'https://cdn.rachidd.com');
        $pdfStorage = new LocalLessonPdfStorage($storage);
        $renderer = new LessonBlockRenderer($pdfStorage);

        $json = json_encode([
            'blocks' => [
                [
                    'id'   => 'b_pdf_1',
                    'type' => 'pdf',
                    'data' => [
                        'url'            => 'https://cdn.rachidd.com/cursos/diagramas-pesados.pdf',
                        'title'          => 'Figura 4.2 — Fluxograma e Gráficos',
                        'mode'           => 'pdfjs_canvas',
                        'start_page'     => 12,
                        'end_page'       => 15,
                        'height'         => '620px',
                        'allow_download' => false,
                        'caption'        => 'Navegue pelas páginas 12 a 15 para inspecionar os vetores.',
                    ],
                ],
            ],
        ]);

        $html = $renderer->render((string)$json);

        $this->assertStringContainsString('ds-block-pdf ds-pdf-mode-pdfjs_canvas', $html);
        $this->assertStringContainsString('Figura 4.2 — Fluxograma e Gráficos', $html);
        $this->assertStringContainsString('Págs. 12&ndash;15', $html);
        $this->assertStringContainsString('data-start-page="12"', $html);
        $this->assertStringContainsString('data-end-page="15"', $html);
        $this->assertStringContainsString('data-allow-download="0"', $html);
        $this->assertStringContainsString('oncontextmenu="return false;"', $html);
        $this->assertStringNotContainsString('⬇ Baixar', $html);
        $this->assertStringContainsString('Navegue pelas páginas 12 a 15 para inspecionar os vetores.', $html);
    }

    public function testLessonBlockRendererConvertsGoogleDriveLinkAndBlocksMaliciousSchemes(): void
    {
        $renderer = new LessonBlockRenderer();

        // 1. Link do Google Drive convertido automaticamente para /preview
        $gdHtml = $renderer->renderPdf([
            'url'   => 'https://drive.google.com/file/d/1AbCdEfGhIjKlMnOpQrStUvWxYz/view?usp=sharing',
            'title' => 'Slides na Nuvem',
        ]);
        $this->assertStringContainsString('https://drive.google.com/file/d/1AbCdEfGhIjKlMnOpQrStUvWxYz/preview', $gdHtml);

        $gdOpenHtml = $renderer->renderPdf([
            'url'   => 'https://drive.google.com/open?id=1AbCdEfGhIjKlMnOpQrStUvWxYz&usp=sharing',
            'title' => 'Slides Open Link',
        ]);
        $this->assertStringContainsString('https://drive.google.com/file/d/1AbCdEfGhIjKlMnOpQrStUvWxYz/preview', $gdOpenHtml);

        // 2. Link do Microsoft OneDrive / SharePoint e tag <iframe> colada diretamente
        $odHtml = $renderer->renderPdf([
            'url'   => '<iframe src="https://onedrive.live.com/embed?resid=12345&amp;authkey=ABC" width="476" height="288"></iframe>',
            'title' => 'Apostila OneDrive',
        ]);
        $this->assertStringContainsString('https://onedrive.live.com/embed?resid=12345&amp;authkey=ABC', $odHtml);

        $spHtml = $renderer->renderPdf([
            'url'   => 'https://empresa-my.sharepoint.com/:b:/g/personal/doc_pdf',
            'title' => 'PDF SharePoint',
        ]);
        $this->assertStringContainsString('action=embedview', $spHtml);

        // 3. Dropbox com conversão de ?dl=0 para ?raw=1
        $dbxHtml = $renderer->renderPdf([
            'url'   => 'https://www.dropbox.com/s/123456/apostila.pdf?dl=0',
            'title' => 'PDF Dropbox',
        ]);
        $this->assertStringContainsString('raw=1', $dbxHtml);

        // 4. Esquema javascript: bloqueado
        $xssHtml = $renderer->renderPdf([
            'url'   => 'javascript:alert(document.cookie)',
            'title' => 'Malicioso',
        ]);
        $this->assertSame('', $xssHtml);
    }

    public function testLessonAdminControllerUploadPdfEndpoint(): void
    {
        $courseRepo = $this->createMock(CourseRepositoryInterface::class);
        $structureRepo = $this->createMock(CourseStructureRepositoryInterface::class);
        $session = $this->createMock(SessionManagerInterface::class);
        $session->method('getCsrfToken')->willReturn('valid_csrf_token');

        $storage = new LocalPublicStorage($this->tempDir, fn() => 'http://localhost');
        $pdfStorage = new LocalLessonPdfStorage($storage);

        $controller = new LessonAdminController(
            $courseRepo,
            $structureRepo,
            $session,
            null,
            $pdfStorage
        );

        $validPdfPath = $this->tempDir . '/upload_test.pdf';
        file_put_contents($validPdfPath, "%PDF-1.4\n%Test PDF\n%%EOF");

        $request = new Request(
            [],
            ['csrf_token' => 'valid_csrf_token'],
            ['REQUEST_METHOD' => 'POST', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            [],
            [
                'pdf' => [
                    'name'     => 'graficos-aula-01.pdf',
                    'type'     => 'application/pdf',
                    'tmp_name' => $validPdfPath,
                    'error'    => UPLOAD_ERR_OK,
                    'size'     => filesize($validPdfPath),
                ],
            ]
        );

        $response = $controller->uploadPdf($request, 10);
        $this->assertSame(200, $response->getStatusCode());
        $payload = json_decode($response->getContent(), true);
        $this->assertTrue($payload['success']);
        $this->assertStringContainsString('/uploads/academy/pdfs/pdf_graficos-aula-01_', $payload['url']);
    }
}
