<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Services;

use DomainSystem\Core\Utils\SlugHelper;
use DomainSystem\Plugins\academy\Contracts\LessonBlockRendererInterface;
use DomainSystem\Plugins\academy\Contracts\LessonFileScaffolderInterface;
use DomainSystem\Plugins\academy\Contracts\ThemePageResolverInterface;
use InvalidArgumentException;
use RuntimeException;

/**
 * Fábrica Automática de Pastas (Módulos) e Arquivos PHP Pré-Configurados de Aulas.
 *
 * Cria fisicamente diretórios e arquivos `.php` dentro de `pages/` do tema ativo,
 * pré-configurados com CSS e JS padrão para resoluções Desktop, Mobile e Tablet,
 * prontos para edição via Blocos.
 *
 * Exemplo:
 *   Título: "Estrutura de Dados"
 *   Arquivo gerado: `estrutura-de-dados.php`
 *   Slug relativo: `pages/estrutura-de-dados.php`
 *   URL pública: `http://localhost/domain-system/cursos/estrutura-de-dados`
 */
class ThemeLessonFileScaffolder implements LessonFileScaffolderInterface
{
    private ThemePageResolverInterface $themeResolver;
    private LessonBlockRendererInterface $blockRenderer;
    private ?string $overridePagesRoot;

    public function __construct(
        ?ThemePageResolverInterface $themeResolver = null,
        ?LessonBlockRendererInterface $blockRenderer = null,
        ?string $overridePagesRoot = null
    ) {
        $this->themeResolver = $themeResolver ?? new ThemePageResolver();
        $this->blockRenderer = $blockRenderer ?? new LessonBlockRenderer();
        $this->overridePagesRoot = $overridePagesRoot;
    }

    public function slugify(string $title): string
    {
        return SlugHelper::slugify($title, 'aula-sem-titulo');
    }

    public function createFolder(string $folderTitle): array
    {
        $trimmed = trim($folderTitle);
        if ($trimmed === '') {
            throw new InvalidArgumentException("O nome da pasta ou módulo não pode estar vazio.");
        }

        // Suporta subpastas seguras ex: "tutoriais/python" ou título simples "Estrutura de Dados"
        $parts = array_filter(explode('/', str_replace('\\', '/', $trimmed)), fn($p) => trim($p) !== '' && $p !== '.' && $p !== '..');
        $slugParts = array_map(fn($p) => $this->slugify($p), $parts);
        if (empty($slugParts)) {
            throw new InvalidArgumentException("Nome de pasta inválido.");
        }

        $folderSlug = implode('/', $slugParts);
        $pagesRoot = $this->resolvePagesDirectory();
        $targetDir = $pagesRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $folderSlug);

        if (!is_dir($targetDir)) {
            if (!@mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
                throw new RuntimeException("Não foi possível criar a pasta física em: pages/{$folderSlug}");
            }
        }

        $realPagesRoot = realpath($pagesRoot);
        $realTargetDir = realpath($targetDir);
        if ($realPagesRoot === false || $realTargetDir === false || !str_starts_with($realTargetDir, $realPagesRoot)) {
            throw new InvalidArgumentException("Violação de segurança ao validar diretório da pasta criada.");
        }

        return [
            'slug'          => $folderSlug,
            'relative_path' => 'pages/' . $folderSlug,
            'full_path'     => $realTargetDir,
        ];
    }

    public function fabricateLessonFile(
        string $title,
        ?string $folder = null,
        ?string $blocksJson = null,
        string $mobileProfile = 'drawer'
    ): array {
        $trimmedTitle = trim($title);
        if ($trimmedTitle === '') {
            throw new InvalidArgumentException("O título da aula/página é obrigatório para fabricar o arquivo PHP.");
        }

        $slug = $this->slugify($trimmedTitle);
        $fileName = $slug . '.php';
        $pagesRoot = $this->resolvePagesDirectory();

        $cleanFolder = '';
        if ($folder !== null && trim($folder) !== '') {
            $normalizedFolder = trim(str_replace('\\', '/', $folder), '/');
            if (str_starts_with($normalizedFolder, 'pages/')) {
                $normalizedFolder = substr($normalizedFolder, 6);
            }
            if ($normalizedFolder !== '') {
                $folderInfo = $this->createFolder($normalizedFolder);
                $cleanFolder = $folderInfo['slug'];
            }
        }

        $relativePath = $cleanFolder !== ''
            ? 'pages/' . $cleanFolder . '/' . $fileName
            : 'pages/' . $fileName;

        $targetDir = $cleanFolder !== ''
            ? $pagesRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanFolder)
            : $pagesRoot;

        $fullPath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

        $phpTemplate = $this->buildPreconfiguredPhpTemplate(
            $trimmedTitle,
            $slug,
            $relativePath,
            $blocksJson,
            $mobileProfile
        );

        if (@file_put_contents($fullPath, $phpTemplate, LOCK_EX) === false) {
            throw new RuntimeException("Falha ao gravar o arquivo pré-configurado em: {$relativePath}");
        }

        $realPagesRoot = realpath($pagesRoot);
        $realFile = realpath($fullPath);
        if ($realPagesRoot === false || $realFile === false || !str_starts_with($realFile, $realPagesRoot)) {
            @unlink($fullPath);
            throw new InvalidArgumentException("Caminho de arquivo fora do diretório permitido.");
        }

        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $publicUrl = $baseUrl . '/cursos/' . $slug;

        return [
            'title'         => $trimmedTitle,
            'slug'          => $slug,
            'file_name'     => $fileName,
            'relative_path' => $relativePath,
            'full_path'     => $realFile,
            'public_url'    => $publicUrl,
        ];
    }

    private function resolvePagesDirectory(): string
    {
        if ($this->overridePagesRoot !== null && $this->overridePagesRoot !== '') {
            if (!is_dir($this->overridePagesRoot)) {
                @mkdir($this->overridePagesRoot, 0755, true);
            }
            return rtrim($this->overridePagesRoot, '/\\');
        }

        $themePath = $this->themeResolver->resolveActiveThemePath();
        $pagesDir = rtrim($themePath, '/\\') . DIRECTORY_SEPARATOR . 'pages';
        if (!is_dir($pagesDir)) {
            @mkdir($pagesDir, 0755, true);
        }
        return $pagesDir;
    }

    private function buildPreconfiguredPhpTemplate(
        string $title,
        string $slug,
        string $relativePath,
        ?string $blocksJson,
        string $mobileProfile
    ): string {
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeProfile = in_array($mobileProfile, ['drawer', 'bottom_bar', 'cinema'], true) ? $mobileProfile : 'drawer';

        if ($blocksJson === null || trim($blocksJson) === '') {
            $defaultBlocks = [
                'time' => time(),
                'blocks' => [
                    [
                        'id'   => 'b_head_1',
                        'type' => 'header',
                        'data' => ['level' => 2, 'text' => $title],
                    ],
                    [
                        'id'   => 'b_par_1',
                        'type' => 'paragraph',
                        'data' => ['text' => 'Conteúdo inicial da aula <strong>' . $safeTitle . '</strong>. Edite este arquivo pelo <code>Editor em Blocos</code> do painel Academy para atualizar automaticamente Desktop e Mobile.'],
                    ],
                ],
            ];
            $blocksJson = (string)json_encode($defaultBlocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $renderedBlocksHtml = $this->blockRenderer->render($blocksJson);
        $encodedJsonComment = base64_encode($blocksJson);

        return <<<PHP
<?php
/**
 * ============================================================================
 * ARQUIVO GERADO PELA FÁBRICA DE AULAS DO ACADEMY (Domain-System v2.4)
 * ============================================================================
 * Título: {$safeTitle}
 * Slug: {$slug}
 * Caminho: {$relativePath}
 * Perfil Mobile: {$safeProfile}
 * DS_BLOCKS_B64: {$encodedJsonComment}
 * ============================================================================
 */
?>
<article class="ds-fabricated-lesson" data-lesson-slug="{$slug}" data-mobile-profile="{$safeProfile}">
    <style>
        /* CSS Padrão Pré-Configurado: Resoluções Desktop, Tablet e Mobile */
        .ds-fabricated-lesson {
            color: var(--text-main, #e2e8f0);
            line-height: 1.75;
            font-size: 1.04rem;
            max-width: 100%;
            box-sizing: border-box;
        }
        .ds-fabricated-lesson .ds-lesson-blocks {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .ds-fabricated-lesson pre {
            max-width: 100%;
            overflow-x: auto;
            border-radius: 10px;
        }
        .ds-fabricated-lesson img,
        .ds-fabricated-lesson iframe,
        .ds-fabricated-lesson video {
            max-width: 100%;
        }

        /* Responsividade Tablet (<= 1024px) */
        @media (max-width: 1024px) {
            .ds-fabricated-lesson {
                font-size: 1rem;
            }
        }

        /* Responsividade Mobile (<= 768px) */
        @media (max-width: 768px) {
            .ds-fabricated-lesson {
                font-size: 0.96rem;
                line-height: 1.65;
            }
            .ds-fabricated-lesson[data-mobile-profile="cinema"] .ds-block-video {
                position: sticky;
                top: 8px;
                z-index: 30;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.65);
            }
        }
    </style>

    <div class="ds-fabricated-lesson-body">
        {$renderedBlocksHtml}
    </div>

    <script>
        /* JS Padrão Pré-Configurado (Desktop & Mobile) */
        (function() {
            const article = document.querySelector('.ds-fabricated-lesson[data-lesson-slug="{$slug}"]');
            if (!article) return;
            article.querySelectorAll('pre code').forEach(function(codeBlock) {
                codeBlock.setAttribute('tabindex', '0');
            });
        })();
    </script>
</article>
PHP;
    }

    public function extractBlocksFromFile(string $relativePath): ?string
    {
        $cleanRel = ltrim(str_replace('\\', '/', $relativePath), '/');
        if (str_starts_with($cleanRel, 'pages/')) {
            $cleanRel = substr($cleanRel, 6);
        }

        $pagesRoot = $this->resolvePagesDirectory();
        $fullPath = $pagesRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanRel);
        if (!is_file($fullPath)) {
            return null;
        }

        $content = (string)@file_get_contents($fullPath);
        if (preg_match('/DS_BLOCKS_B64:\s*([A-Za-z0-9+\/=]+)/', $content, $matches)) {
            $decoded = base64_decode($matches[1], true);
            return $decoded !== false ? $decoded : null;
        }

        return null;
    }

    public function inspectLessonFile(string $relativePath): array
    {
        $cleanRel = ltrim(str_replace('\\', '/', $relativePath), '/');
        if (str_starts_with($cleanRel, 'pages/')) {
            $cleanRel = substr($cleanRel, 6);
        }

        $pagesRoot = $this->resolvePagesDirectory();
        $fullPath = $pagesRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanRel);

        $fileName = basename($cleanRel);
        $fallbackTitle = ucwords(str_replace(['-', '_'], ' ', preg_replace('/\.(php|html)$/i', '', $fileName)));

        if (!is_file($fullPath)) {
            return [
                'title'          => $fallbackTitle,
                'slug'           => $this->slugify($fallbackTitle),
                'mobile_profile' => 'drawer',
                'has_blocks'     => false,
                'blocks_json'    => null,
            ];
        }

        // Lê até 8KB iniciais para extração rápida de cabeçalho e <h1>
        $handle = @fopen($fullPath, 'r');
        $content = $handle ? (string)@fread($handle, 8192) : (string)@file_get_contents($fullPath);
        if ($handle) {
            @fclose($handle);
        }

        $title = null;
        // 1. Tenta extrair do cabeçalho docblock: * Título: ... ou * Title: ...
        if (preg_match('/(?:\*|\/\/)\s*(?:T[íi]tulo|Title)\s*:\s*([^\r\n*]+)/iu', $content, $m)) {
            $candidate = trim($m[1]);
            if ($candidate !== '') {
                $title = $candidate;
            }
        }

        // 2. Se não encontrou, tenta extrair da primeira tag <h1>...</h1>
        if ($title === null && preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $content, $m)) {
            $candidate = trim(strip_tags($m[1]));
            if ($candidate !== '') {
                $title = $candidate;
            }
        }

        if ($title === null || $title === '') {
            $title = $fallbackTitle;
        }

        // 3. Perfil Mobile
        $mobileProfile = 'drawer';
        if (preg_match('/(?:\*|\/\/)\s*(?:Perfil\s*Mobile|Mobile\s*Profile)\s*:\s*([a-z_]+)/iu', $content, $m)) {
            $p = strtolower(trim($m[1]));
            if (in_array($p, ['drawer', 'bottom_bar', 'cinema'], true)) {
                $mobileProfile = $p;
            }
        }

        // 4. Blocos em Base64
        $blocksJson = null;
        $hasBlocks = false;
        if (preg_match('/DS_BLOCKS_B64:\s*([A-Za-z0-9+\/=]+)/', $content, $m)) {
            $decoded = base64_decode($m[1], true);
            if ($decoded !== false && trim($decoded) !== '') {
                $blocksJson = $decoded;
                $hasBlocks = true;
            }
        }

        return [
            'title'          => $title,
            'slug'           => $this->slugify($title),
            'mobile_profile' => $mobileProfile,
            'has_blocks'     => $hasBlocks,
            'blocks_json'    => $blocksJson,
        ];
    }
}
