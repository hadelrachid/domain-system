<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Services;

use DomainSystem\Plugins\academy\Contracts\CoursePackageServiceInterface;
use DomainSystem\Plugins\academy\Contracts\CourseRepositoryInterface;
use DomainSystem\Plugins\academy\Contracts\CourseStructureRepositoryInterface;
use DomainSystem\Plugins\academy\Contracts\LessonFileScaffolderInterface;
use DomainSystem\Plugins\academy\Contracts\ThemePageResolverInterface;
use FilesystemIterator;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

class CoursePackageService implements CoursePackageServiceInterface
{
    private CourseRepositoryInterface $courseRepo;
    private CourseStructureRepositoryInterface $structureRepo;
    private ThemePageResolverInterface $themeResolver;
    private CourseService $courseService;
    private ?LessonFileScaffolderInterface $scaffolder;

    /**
     * Extensões de arquivos permitidas para extração segura (Anti-RCE / Anti-ZipSlip).
     */
    private const ALLOWED_EXTENSIONS = [
        'php', 'html', 'css', 'js', 'json', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'pdf'
    ];

    public function __construct(
        CourseRepositoryInterface $courseRepo,
        CourseStructureRepositoryInterface $structureRepo,
        ThemePageResolverInterface $themeResolver,
        CourseService $courseService,
        ?LessonFileScaffolderInterface $scaffolder = null
    ) {
        $this->courseRepo = $courseRepo;
        $this->structureRepo = $structureRepo;
        $this->themeResolver = $themeResolver;
        $this->courseService = $courseService;
        $this->scaffolder = $scaffolder;
    }

    public function exportCourseZip(int $courseId): string
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException("A extensão PHP ZipArchive é necessária para compactar cursos.");
        }

        $course = $this->courseRepo->findById($courseId);
        if (!$course) {
            throw new InvalidArgumentException("Curso ID {$courseId} não encontrado para exportação.");
        }

        $modules = $this->structureRepo->getModules($courseId);
        $allLessons = $this->structureRepo->getLessons($courseId);

        // Agrupa aulas por módulo
        $lessonsByMod = [];
        $standaloneLessons = [];
        foreach ($allLessons as $les) {
            $mId = $les['module_id'] !== null ? (int)$les['module_id'] : null;
            if ($mId !== null && $mId > 0) {
                $lessonsByMod[$mId][] = $les;
            } else {
                $standaloneLessons[] = $les;
            }
        }

        $structure = [];
        foreach ($modules as $m) {
            $mId = (int)$m['id'];
            $structure[] = [
                'title'       => (string)$m['title'],
                'folder_slug' => (string)($m['folder_slug'] ?? ''),
                'position'    => (int)$m['position'],
                'lessons'     => array_map(static function (array $l): array {
                    return [
                        'title'          => (string)$l['title'],
                        'slug'           => (string)$l['slug'],
                        'source_type'    => (string)$l['source_type'],
                        'source'         => (string)($l['source'] ?? ''),
                        'content'        => (string)($l['content'] ?? ''),
                        'mobile_profile' => (string)($l['mobile_profile'] ?? 'drawer'),
                        'status'         => (string)($l['status'] ?? 'published'),
                    ];
                }, $lessonsByMod[$mId] ?? []),
            ];
        }

        if (!empty($standaloneLessons)) {
            $structure[] = [
                'title'       => 'Módulo Geral',
                'folder_slug' => '',
                'position'    => 999,
                'lessons'     => array_map(static function (array $l): array {
                    return [
                        'title'          => (string)$l['title'],
                        'slug'           => (string)$l['slug'],
                        'source_type'    => (string)$l['source_type'],
                        'source'         => (string)($l['source'] ?? ''),
                        'content'        => (string)($l['content'] ?? ''),
                        'mobile_profile' => (string)($l['mobile_profile'] ?? 'drawer'),
                        'status'         => (string)($l['status'] ?? 'published'),
                    ];
                }, $standaloneLessons),
            ];
        }

        // Manifesto do Curso
        $manifest = [
            'schema_version' => '2.6.0',
            'generator'      => 'DomainSystem Academy LMS',
            'exported_at'    => date('c'),
            'course'         => [
                'title'       => (string)$course['title'],
                'slug'        => (string)$course['slug'],
                'description' => (string)($course['description'] ?? ''),
                'price'       => (float)($course['price'] ?? 0.00),
                'access_type' => (string)($course['access_type'] ?? 'free'),
                'category'    => (string)($course['category'] ?? ''),
                'status'      => (string)($course['status'] ?? 'published'),
            ],
            'structure'      => $structure,
        ];

        // Cria arquivo ZIP temporário
        $tempDir = sys_get_temp_dir();
        $safeSlug = preg_replace('/[^a-z0-9_-]/i', '_', (string)$course['slug']) ?: 'curso';
        $zipFilePath = $tempDir . DIRECTORY_SEPARATOR . 'academy_export_' . $safeSlug . '_' . uniqid() . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Não foi possível criar o arquivo ZIP temporário em: {$zipFilePath}");
        }

        // Adiciona manifesto course.json
        $zip->addFromString('course.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        // Coleta os arquivos .php do curso no tema ativo
        $themePath = $this->themeResolver->resolveActiveThemePath();
        $pagesDir = $themePath . '/pages';
        $filesAdded = [];

        // 1. Arquivos da pasta específica do curso: pages/{course_slug}/
        $coursePagesDir = $pagesDir . '/' . $course['slug'];
        if (is_dir($coursePagesDir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($coursePagesDir, FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if ($file->isFile() && in_array(strtolower($file->getExtension()), self::ALLOWED_EXTENSIONS, true)) {
                    $fullPath = $file->getPathname();
                    $relInPages = substr($fullPath, strlen($pagesDir) + 1);
                    $zipEntryName = 'pages/' . str_replace('\\', '/', $relInPages);
                    $zip->addFile($fullPath, $zipEntryName);
                    $filesAdded[$zipEntryName] = true;
                }
            }
        }

        // 2. Arquivos apontados nas aulas como source_type === 'theme_file'
        foreach ($allLessons as $les) {
            $source = trim((string)($les['source'] ?? ''));
            if ($source === '' || str_starts_with($source, 'http://') || str_starts_with($source, 'https://')) {
                continue;
            }

            // Converte "pages/solid/aula.php" para o arquivo físico no tema
            $cleanSource = ltrim(str_replace('\\', '/', $source), '/');
            $fullPath = $themePath . '/' . $cleanSource;

            if (is_file($fullPath)) {
                $zipEntryName = $cleanSource;
                if (!isset($filesAdded[$zipEntryName])) {
                    $zip->addFile($fullPath, $zipEntryName);
                    $filesAdded[$zipEntryName] = true;
                }
            }
        }

        $zip->close();

        return $zipFilePath;
    }

    public function importCourseZip(string $zipFilePath, ?string $overrideThemePath = null): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException("A extensão PHP ZipArchive é necessária para descompactar cursos.");
        }

        if (!is_file($zipFilePath) || filesize($zipFilePath) === 0) {
            throw new InvalidArgumentException("O arquivo ZIP de curso informado é inválido ou está vazio.");
        }

        $zip = new ZipArchive();
        if ($zip->open($zipFilePath) !== true) {
            throw new RuntimeException("Falha ao abrir o arquivo ZIP para descompactação.");
        }

        $manifestContent = $zip->getFromName('course.json');
        if ($manifestContent === false) {
            $zip->close();
            throw new InvalidArgumentException("O arquivo ZIP não contém o manifesto obrigatório 'course.json'.");
        }

        $manifest = json_decode($manifestContent, true);
        if (!is_array($manifest) || empty($manifest['course']['title'])) {
            $zip->close();
            throw new InvalidArgumentException("O manifesto 'course.json' possui estrutura corrompida ou título ausente.");
        }

        $courseData = $manifest['course'];
        $courseTitle = trim((string)$courseData['title']);
        $rawCourseSlug = trim((string)($courseData['slug'] ?? ''));

        // Resolve caminho seguro de destino para as páginas no tema ativo
        $targetThemePath = $overrideThemePath ?? $this->themeResolver->resolveActiveThemePath();
        $targetPagesDir = $targetThemePath . DIRECTORY_SEPARATOR . 'pages';

        if (!is_dir($targetPagesDir)) {
            if (!@mkdir($targetPagesDir, 0755, true) && !is_dir($targetPagesDir)) {
                $zip->close();
                throw new RuntimeException("Não foi possível criar o diretório de destino em: {$targetPagesDir}");
            }
        }

        $realTargetPagesDir = realpath($targetPagesDir);
        if ($realTargetPagesDir === false) {
            $zip->close();
            throw new RuntimeException("Falha ao resolver caminho real de pages do tema ativo.");
        }

        // Extrai arquivos de forma segura com proteção Anti-ZipSlip
        $extractedFiles = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = (string)$zip->getNameIndex($i);

            // Ignora arquivos do sistema operacional e o próprio manifesto
            if ($entryName === 'course.json' ||
                str_starts_with($entryName, '__MACOSX') ||
                str_contains($entryName, '.DS_Store') ||
                str_ends_with($entryName, '/')
            ) {
                continue;
            }

            // Proteção contra path traversal (Zip-Slip)
            if (str_contains($entryName, '..') || str_starts_with($entryName, '/') || str_starts_with($entryName, '\\')) {
                continue;
            }

            // Valida extensão permitida
            $ext = strtolower((string)pathinfo($entryName, PATHINFO_EXTENSION));
            if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
                continue;
            }

            // Normaliza caminho de destino: se começa com "pages/", remove o prefixo relativo
            $cleanRelative = str_starts_with($entryName, 'pages/') ? substr($entryName, 6) : $entryName;
            $destPath = $realTargetPagesDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanRelative);

            // Garante criação da subpasta de destino
            $destDir = dirname($destPath);
            if (!is_dir($destDir)) {
                @mkdir($destDir, 0755, true);
            }

            // Valida se o destino final permanece estritamente dentro de $realTargetPagesDir
            $realDestDir = realpath($destDir);
            if ($realDestDir === false || !str_starts_with($realDestDir, $realTargetPagesDir)) {
                continue; // Violação de escopo ignorada com segurança
            }

            $content = $zip->getFromIndex($i);
            if ($content !== false) {
                file_put_contents($destPath, $content);
                $extractedFiles[] = 'pages/' . str_replace('\\', '/', $cleanRelative);
            }
        }

        $zip->close();

        // Preserva o slug original do manifesto se não colidir com um curso existente; caso colida, gera slug único
        $targetSlug = $rawCourseSlug !== '' ? $rawCourseSlug : $this->courseService->generateUniqueSlug($courseTitle);
        if ($this->courseRepo->findBySlug($targetSlug) !== null) {
            $uniqueSlug = $this->courseService->generateUniqueSlug($courseTitle);
        } else {
            $uniqueSlug = $targetSlug;
        }

        // Cria o curso no banco/repositório
        $newCourseId = $this->courseRepo->create([
            'title'       => $courseTitle,
            'slug'        => $uniqueSlug,
            'description' => (string)($courseData['description'] ?? ''),
            'price'       => (float)($courseData['price'] ?? 0.00),
            'access_type' => (string)($courseData['access_type'] ?? 'free'),
            'category'    => (string)($courseData['category'] ?? ''),
            'status'      => (string)($courseData['status'] ?? 'published'),
        ]);

        // Restaura estrutura de módulos e aulas
        $rawStructure = $manifest['structure'] ?? [];
        $sanitizedStructure = [];
        $totalLessons = 0;

        foreach ($rawStructure as $modIdx => $mod) {
            $modTitle = trim((string)($mod['title'] ?? 'Módulo ' . ($modIdx + 1)));
            $modFolderSlug = trim((string)($mod['folder_slug'] ?? ''));
            $modPosition = isset($mod['position']) ? (int)$mod['position'] : ($modIdx + 1);

            $lessons = [];
            if (!empty($mod['lessons']) && is_array($mod['lessons'])) {
                foreach ($mod['lessons'] as $les) {
                    $lTitle = trim((string)($les['title'] ?? 'Nova Aula'));
                    $lSlug = trim((string)($les['slug'] ?? ''));
                    $lSourceType = (string)($les['source_type'] ?? 'theme_file');
                    $lSource = (string)($les['source'] ?? '');

                    // Se a aula for do tipo theme_file e o slug mudou, atualiza referências
                    if ($rawCourseSlug !== '' && $uniqueSlug !== $rawCourseSlug && str_contains($lSource, 'pages/' . $rawCourseSlug . '/')) {
                        $lSource = str_replace('pages/' . $rawCourseSlug . '/', 'pages/' . $uniqueSlug . '/', $lSource);
                    }

                    $lessons[] = [
                        'title'          => $lTitle,
                        'slug'           => $lSlug,
                        'source_type'    => $lSourceType,
                        'source'         => $lSource,
                        'content'        => (string)($les['content'] ?? ''),
                        'mobile_profile' => (string)($les['mobile_profile'] ?? 'drawer'),
                        'status'         => (string)($les['status'] ?? 'published'),
                    ];
                    $totalLessons++;
                }
            }

            $sanitizedStructure[] = [
                'title'       => $modTitle,
                'folder_slug' => $modFolderSlug,
                'position'    => $modPosition,
                'lessons'     => $lessons,
            ];
        }

        if (!empty($sanitizedStructure)) {
            $this->structureRepo->replaceCourseStructure($newCourseId, $sanitizedStructure);
        }

        return [
            'course_id'       => $newCourseId,
            'title'           => $courseTitle,
            'slug'            => $uniqueSlug,
            'modules_count'   => count($sanitizedStructure),
            'lessons_count'   => $totalLessons,
            'extracted_files' => $extractedFiles,
        ];
    }
}
