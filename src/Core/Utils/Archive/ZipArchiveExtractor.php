<?php

declare(strict_types=1);

namespace DomainSystem\Core\Utils\Archive;

use Exception;
use ZipArchive;

/**
 * ════════════════════════════════════════════════════════════════════════════
 * CLASSE: ZipArchiveExtractor (Extração Segura em Quarentena)
 * ════════════════════════════════════════════════════════════════════════════
 *
 * Responsável por validar e extrair pacotes ZIP de plugins e temas em
 * diretório temporário de quarentena antes da movimentação final.
 *
 * MEDIDAS DE SEGURANÇA NA EXTRAÇÃO:
 * 1. Bloqueia Path Traversal relativo ("../", "..\") e segmentos "." / "..".
 * 2. Bloqueia caminhos absolutos Unix/Windows ("/home/...", "\...", "C:\...")
 *    e wrappers de stream ("phar://", "file://").
 * 3. Bloqueia links simbólicos (symlinks UNIX no cabeçalho externo do ZIP).
 * 4. Realiza extração controlada via stream entrada por entrada (sem delegar
 *    caminhos ao ZipArchive::extractTo nativo), validando que o destino real
 *    (realpath) permanece estritamente confinado dentro da pasta de quarentena.
 */
class ZipArchiveExtractor implements ExtractorInterface
{
    public function extract(string $archivePath, string $destinationPath, string $descriptorFile = 'plugin.json'): string
    {
        $zip = new ZipArchive();

        if ($zip->open($archivePath) !== true) {
            throw new Exception("Não foi possível abrir o arquivo ZIP com ZipArchive.");
        }

        $hasDescriptor = false;
        $componentDirName = null;
        $escapedDescriptor = preg_quote($descriptorFile, '#');

        try {
            // 1. ANÁLISE DO PACOTE E VALIDAÇÃO ESTRITA DE CAMINHOS E SYMLINKS
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                if (!is_string($filename) || $filename === '') {
                    throw new Exception("Vulnerabilidade Crítica (Zip-Slip): Entrada inválida no pacote ZIP.");
                }

                $this->assertSafeEntry($zip, $i, $filename);

                $normalized = str_replace('\\', '/', $filename);
                if (preg_match('#^([a-zA-Z0-9_-]+)/' . $escapedDescriptor . '$#', $normalized, $matches)) {
                    $hasDescriptor = true;
                    $componentDirName = $matches[1];
                }
            }

            // 2. PREPARAÇÃO DA QUARENTENA
            $baseTemp = defined('DOMAIN_SYSTEM_ROOT') && is_dir(DOMAIN_SYSTEM_ROOT . '/temp')
                ? DOMAIN_SYSTEM_ROOT . '/temp'
                : sys_get_temp_dir();
            $tempDir = $baseTemp . '/quarentena_zip_' . bin2hex(random_bytes(8));
            if (!@mkdir($tempDir, 0755, true) && !is_dir($tempDir)) {
                throw new Exception("Não foi possível criar o diretório de quarentena para extração.");
            }

            // Se o ZIP não tem pasta raiz (os arquivos estão soltos na raiz do ZIP)
            if (!$hasDescriptor) {
                $idx = $zip->locateName($descriptorFile);
                if ($idx !== false) {
                    $json = $zip->getFromIndex($idx);
                    $data = is_string($json) ? json_decode($json, true) : null;
                    if (is_array($data) && isset($data['name']) && is_string($data['name'])) {
                        $hasDescriptor = true;
                        $componentDirName = preg_replace('/[^a-zA-Z0-9_-]+/', '-', strtolower($data['name'])) ?: '';
                        $componentDirName = trim($componentDirName, '-');

                        if ($componentDirName === '') {
                            throw new Exception("Nome de componente inválido no manifesto ($descriptorFile).");
                        }

                        $this->safeExtract($zip, $tempDir);
                        $zip->close();

                        $this->moveToDestination($tempDir, $destinationPath, $componentDirName, $descriptorFile);
                        return $componentDirName;
                    }
                }
            }

            if (!$hasDescriptor || !$componentDirName) {
                if (is_dir($tempDir)) {
                    $this->deleteDirectory($tempDir);
                }
                throw new Exception("ZIP inválido: Não possui o arquivo manifesto ($descriptorFile) válido no pacote.");
            }

            // 3. EXTRAÇÃO CONTROLADA PARA QUARENTENA
            $this->safeExtract($zip, $tempDir);
            $zip->close();

            // 4. TRANSFERÊNCIA DA QUARENTENA PARA A PASTA FINAL
            $sourcePath = $tempDir . '/' . $componentDirName;
            $this->moveToDestination($sourcePath, $destinationPath, $componentDirName, $descriptorFile);
            $this->deleteDirectory($tempDir);

            return $componentDirName;
        } catch (\Throwable $e) {
            @$zip->close();
            if (isset($tempDir) && is_dir($tempDir)) {
                $this->deleteDirectory($tempDir);
            }
            throw $e instanceof Exception ? $e : new Exception($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * Valida se uma entrada do ZIP é segura contra Zip-Slip, caminhos absolutos,
     * letras de unidade, wrappers de stream e symlinks UNIX.
     */
    private function assertSafeEntry(ZipArchive $zip, int $index, string $filename): void
    {
        if (strpos($filename, "\0") !== false) {
            throw new Exception("Vulnerabilidade Crítica (Zip-Slip): Byte nulo detectado no caminho do ZIP.");
        }

        // Bloqueia caminhos absolutos Unix (/...) ou Windows (\... ou \\...)
        if (str_starts_with($filename, '/') || str_starts_with($filename, '\\')) {
            throw new Exception("Vulnerabilidade Crítica (Zip-Slip): Caminho absoluto não permitido no pacote ZIP.");
        }

        // Bloqueia letras de unidade do Windows (C:...) e wrappers (phar://, file://, etc.)
        if (preg_match('/^[a-zA-Z]:|^[a-zA-Z0-9+.-]+:\/\//', $filename) === 1) {
            throw new Exception("Vulnerabilidade Crítica (Zip-Slip): Drive ou protocolo absoluto não permitido no pacote ZIP.");
        }

        // Bloqueia traversal relativo bruto
        if (strpos($filename, '../') !== false || strpos($filename, '..\\') !== false) {
            throw new Exception("Vulnerabilidade Crítica (Zip-Slip): O pacote contém caminhos maliciosos.");
        }

        $normalized = str_replace('\\', '/', $filename);
        $segments = explode('/', rtrim($normalized, '/'));
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw new Exception("Vulnerabilidade Crítica (Zip-Slip): Segmento de diretório inválido no pacote ZIP.");
            }
        }

        // Bloqueia links simbólicos UNIX (S_IFLNK = 0120000 nos atributos externos)
        $opsys = 0;
        $attr = 0;
        if ($zip->getExternalAttributesIndex($index, $opsys, $attr)) {
            $unixMode = ($attr >> 16) & 0170000;
            if ($unixMode === 0120000) {
                throw new Exception("Vulnerabilidade Crítica (Zip-Slip): Links simbólicos (symlinks) não são permitidos no pacote ZIP.");
            }
        }
    }

    /**
     * Extrai os arquivos via stream entrada por entrada (sem usar ZipArchive::extractTo),
     * garantindo confinamento real dentro de $tempDir via realpath().
     */
    private function safeExtract(ZipArchive $zip, string $tempDir): void
    {
        $realTempRoot = realpath($tempDir);
        if ($realTempRoot === false) {
            throw new Exception("Falha de Segurança: Diretório de quarentena inacessível.");
        }
        $realTempPrefix = rtrim($realTempRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if (!is_string($filename) || $filename === '') {
                throw new Exception("Falha de Segurança: Entrada inválida dentro do ZIP.");
            }

            $this->assertSafeEntry($zip, $i, $filename);

            $normalized = str_replace('\\', '/', $filename);
            $isDirectory = str_ends_with($normalized, '/');
            $relativePath = str_replace('/', DIRECTORY_SEPARATOR, rtrim($normalized, '/'));
            $targetPath = $realTempPrefix . $relativePath;

            if ($isDirectory) {
                if (!is_dir($targetPath) && !@mkdir($targetPath, 0755, true) && !is_dir($targetPath)) {
                    throw new Exception("Falha ao criar diretório na quarentena.");
                }
                $realTargetDir = realpath($targetPath);
                if ($realTargetDir === false || !str_starts_with($realTargetDir . DIRECTORY_SEPARATOR, $realTempPrefix)) {
                    throw new Exception("Falha de Segurança: Escape de diretório detectado na quarentena.");
                }
                continue;
            }

            $parentDir = dirname($targetPath);
            if (!is_dir($parentDir) && !@mkdir($parentDir, 0755, true) && !is_dir($parentDir)) {
                throw new Exception("Falha ao criar diretório pai na quarentena.");
            }

            $realParent = realpath($parentDir);
            if (
                $realParent === false
                || ($realParent !== $realTempRoot && !str_starts_with($realParent . DIRECTORY_SEPARATOR, $realTempPrefix))
            ) {
                throw new Exception("Falha de Segurança: Caminho fora da área de quarentena.");
            }

            $stream = $zip->getStream($filename);
            if (!is_resource($stream)) {
                throw new Exception("Falha ao ler stream do arquivo no pacote ZIP.");
            }

            $out = @fopen($targetPath, 'wb');
            if (!is_resource($out)) {
                fclose($stream);
                throw new Exception("Falha ao gravar arquivo na quarentena.");
            }

            stream_copy_to_stream($stream, $out);
            fclose($out);
            fclose($stream);
        }
    }

    private function moveToDestination(string $sourcePath, string $destinationPath, string $componentDirName, string $descriptorFile): void
    {
        if (preg_match('/^[a-zA-Z0-9_-]+$/', $componentDirName) !== 1) {
            $this->deleteDirectory($sourcePath);
            throw new Exception("Bloqueio de Segurança: Nome de diretório de componente inválido.");
        }

        if (!is_dir($destinationPath)) {
            @mkdir($destinationPath, 0755, true);
        }

        $targetPath = $destinationPath . '/' . $componentDirName;

        // Proteção Ring 0: impede sobrescrever um SystemApp protegido via upload ZIP
        if (file_exists($targetPath . '/' . $descriptorFile)) {
            $meta = json_decode((string) file_get_contents($targetPath . '/' . $descriptorFile), true);
            if (!empty($meta['core'])) {
                $this->deleteDirectory($sourcePath);
                throw new Exception("Bloqueio de Segurança: Não é permitido sobrescrever um SystemApp protegido via upload ZIP.");
            }
        }

        if (file_exists($targetPath)) {
            $this->deleteDirectory($targetPath);
        }

        if (!@rename($sourcePath, $targetPath)) {
            $this->copyDirectory($sourcePath, $targetPath);
            $this->deleteDirectory($sourcePath);
        }
    }

    private function copyDirectory(string $source, string $dest): void
    {
        if (!is_dir($dest)) {
            @mkdir($dest, 0755, true);
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
            $target = $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathname();
            if ($item->isDir()) {
                if (!is_dir($target)) {
                    @mkdir($target, 0755, true);
                }
            } else {
                copy($item->getPathname(), $target);
            }
        }
    }

    private function deleteDirectory(string $dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }
        if (!is_dir($dir)) {
            return unlink($dir);
        }

        $items = scandir($dir);
        if ($items === false) {
            return false;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        return rmdir($dir);
    }
}
