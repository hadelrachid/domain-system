<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Infrastructure;

use DomainSystem\Core\Update\Exception\PackageIntegrityException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

/**
 * Extrator seguro de pacotes ZIP em diretório de quarentena.
 *
 * Aplica defesas contra:
 * - Zip-Slip (`..`, caminhos absolutos, barras invertidas, letras de unidade, bytes nulos)
 * - Links simbólicos (atributos Unix S_IFLNK no cabeçalho ZIP ou link criado em disco)
 * - Colisão de caminhos case-insensitive (`Foo.php` vs `foo.php`)
 * - Zip-Bomb (limite de quantidade de arquivos, tamanho total descompactado e razão de compressão)
 */
final class ZipPackageExtractor
{
    public const DEFAULT_MAX_FILES = 20000;
    public const DEFAULT_MAX_TOTAL_BYTES = 157286400; // 150 MB
    public const DEFAULT_MAX_COMPRESSION_RATIO = 100.0;
    private const RATIO_MIN_UNCOMPRESSED_BYTES = 1024;

    public function __construct(
        private readonly int $maxFiles = self::DEFAULT_MAX_FILES,
        private readonly int $maxTotalBytes = self::DEFAULT_MAX_TOTAL_BYTES,
        private readonly float $maxCompressionRatio = self::DEFAULT_MAX_COMPRESSION_RATIO,
    ) {
    }

    /**
     * Extrai todos os arquivos do ZIP para `$destinationDir`.
     * Em caso de qualquer violação, remove `$destinationDir` antes de lançar `PackageIntegrityException`.
     *
     * @throws PackageIntegrityException
     */
    public function extract(string $archivePath, string $destinationDir): void
    {
        if (!is_file($archivePath) || !is_readable($archivePath)) {
            throw new PackageIntegrityException(sprintf('Arquivo ZIP de pacote não encontrado ou ilegível: %s', $archivePath));
        }

        $zip = new ZipArchive();
        $openResult = $zip->open($archivePath, ZipArchive::RDONLY);
        if ($openResult !== true) {
            throw new PackageIntegrityException(sprintf('Falha ao abrir arquivo ZIP (código %s).', (string)$openResult));
        }

        if (!is_dir($destinationDir) && !@mkdir($destinationDir, 0755, true) && !is_dir($destinationDir)) {
            $zip->close();
            throw new PackageIntegrityException(sprintf('Não foi possível criar diretório de quarentena: %s', $destinationDir));
        }

        try {
            $numFiles = $zip->numFiles;
            if ($numFiles > $this->maxFiles) {
                throw new PackageIntegrityException(sprintf(
                    'O pacote excede o limite máximo de entradas permitido (%d > %d).',
                    $numFiles,
                    $this->maxFiles
                ));
            }

            $totalUncompressedBytes = 0;
            $seenLowerPaths = [];

            for ($i = 0; $i < $numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if (!is_array($stat) || !isset($stat['name'])) {
                    throw new PackageIntegrityException(sprintf('Não foi possível ler metadados da entrada #%d do ZIP.', $i));
                }

                $entryName = (string)$stat['name'];
                $this->assertSafeRelativePath($entryName);

                $opsys = 0;
                $attr = 0;
                if ($zip->getExternalAttributesIndex($i, $opsys, $attr)) {
                    $unixMode = ($attr >> 16) & 0xFFFF;
                    if (($unixMode & 0xF000) === 0xA000) {
                        throw new PackageIntegrityException(sprintf(
                            'Link simbólico detectado e bloqueado no pacote: "%s".',
                            $entryName
                        ));
                    }
                }

                // Diretório puro
                if (str_ends_with($entryName, '/')) {
                    $dirPath = $destinationDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, rtrim($entryName, '/'));
                    if (!is_dir($dirPath) && !@mkdir($dirPath, 0755, true) && !is_dir($dirPath)) {
                        throw new PackageIntegrityException(sprintf('Falha ao criar diretório na quarentena: "%s".', $entryName));
                    }
                    continue;
                }

                $lowerKey = strtolower($entryName);
                if (isset($seenLowerPaths[$lowerKey])) {
                    throw new PackageIntegrityException(sprintf(
                        'Colisão de caminhos case-insensitive detectada no pacote: "%s" e "%s".',
                        $seenLowerPaths[$lowerKey],
                        $entryName
                    ));
                }
                $seenLowerPaths[$lowerKey] = $entryName;

                $declaredUncompressed = (int)($stat['size'] ?? 0);
                $declaredCompressed = (int)($stat['comp_size'] ?? 0);

                if ($declaredUncompressed < 0 || $declaredCompressed < 0) {
                    throw new PackageIntegrityException(sprintf('Tamanho negativo inválido na entrada "%s".', $entryName));
                }

                if ($declaredUncompressed >= self::RATIO_MIN_UNCOMPRESSED_BYTES) {
                    if ($declaredCompressed === 0) {
                        throw new PackageIntegrityException(sprintf('Suspeita de Zip-Bomb na entrada "%s" (comp_size=0).', $entryName));
                    }
                    $ratio = (float)$declaredUncompressed / (float)$declaredCompressed;
                    if ($ratio > $this->maxCompressionRatio) {
                        throw new PackageIntegrityException(sprintf(
                            'Suspeita de Zip-Bomb na entrada "%s": razão de compressão %.1f excede o limite de %.1f.',
                            $entryName,
                            $ratio,
                            $this->maxCompressionRatio
                        ));
                    }
                }

                if (($totalUncompressedBytes + $declaredUncompressed) > $this->maxTotalBytes) {
                    throw new PackageIntegrityException(sprintf(
                        'O pacote excede o limite máximo de tamanho descompactado (%d bytes).',
                        $this->maxTotalBytes
                    ));
                }

                $targetFile = $destinationDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $entryName);
                $parentDir = dirname($targetFile);
                if (!is_dir($parentDir) && !@mkdir($parentDir, 0755, true) && !is_dir($parentDir)) {
                    throw new PackageIntegrityException(sprintf('Não foi possível criar diretório pai para "%s".', $entryName));
                }

                $stream = $zip->getStream($entryName);
                if (!is_resource($stream)) {
                    throw new PackageIntegrityException(sprintf('Não foi possível abrir stream da entrada "%s".', $entryName));
                }

                $out = @fopen($targetFile, 'wb');
                if (!is_resource($out)) {
                    fclose($stream);
                    throw new PackageIntegrityException(sprintf('Não foi possível criar arquivo extraído "%s".', $entryName));
                }

                $writtenForFile = 0;
                try {
                    while (!feof($stream)) {
                        $chunk = fread($stream, 65536);
                        if ($chunk === false) {
                            throw new PackageIntegrityException(sprintf('Erro de leitura ao extrair "%s".', $entryName));
                        }
                        $chunkLen = strlen($chunk);
                        if ($chunkLen === 0) {
                            break;
                        }

                        $writtenForFile += $chunkLen;
                        $totalUncompressedBytes += $chunkLen;

                        if ($totalUncompressedBytes > $this->maxTotalBytes) {
                            throw new PackageIntegrityException(sprintf(
                                'Limite máximo descompactado (%d bytes) excedido durante a extração de "%s".',
                                $this->maxTotalBytes,
                                $entryName
                            ));
                        }

                        if ($writtenForFile >= self::RATIO_MIN_UNCOMPRESSED_BYTES && $declaredCompressed > 0) {
                            $liveRatio = (float)$writtenForFile / (float)$declaredCompressed;
                            if ($liveRatio > $this->maxCompressionRatio) {
                                throw new PackageIntegrityException(sprintf(
                                    'Suspeita de Zip-Bomb em streaming na entrada "%s" (razão %.1f).',
                                    $entryName,
                                    $liveRatio
                                ));
                            }
                        }

                        $written = fwrite($out, $chunk);
                        if ($written === false || $written !== $chunkLen) {
                            throw new PackageIntegrityException(sprintf('Erro de escrita ao extrair "%s".', $entryName));
                        }
                    }
                } finally {
                    fclose($out);
                    fclose($stream);
                }

                if ($writtenForFile !== $declaredUncompressed) {
                    throw new PackageIntegrityException(sprintf(
                        'Tamanho extraído (%d) diverge do cabeçalho ZIP (%d) para "%s".',
                        $writtenForFile,
                        $declaredUncompressed,
                        $entryName
                    ));
                }

                if (is_link($targetFile)) {
                    throw new PackageIntegrityException(sprintf('Link simbólico detectado após extração: "%s".', $entryName));
                }
            }
        } catch (\Throwable $e) {
            $zip->close();
            self::removeDirectory($destinationDir);
            if ($e instanceof PackageIntegrityException) {
                throw $e;
            }
            throw new PackageIntegrityException('Erro ao extrair pacote ZIP: ' . $e->getMessage(), 0, $e);
        }

        $zip->close();
    }

    /**
     * Valida que o caminho da entrada do ZIP é seguro e relativo.
     *
     * @throws PackageIntegrityException
     */
    public function assertSafeRelativePath(string $path): void
    {
        if ($path === '' || str_contains($path, "\0")) {
            throw new PackageIntegrityException('Caminho de arquivo vazio ou contendo byte nulo no pacote.');
        }

        if (str_contains($path, '\\')) {
            throw new PackageIntegrityException(sprintf('Barra invertida não permitida no caminho do pacote: "%s".', $path));
        }

        if (str_starts_with($path, '/')) {
            throw new PackageIntegrityException(sprintf('Caminho absoluto não permitido no pacote: "%s".', $path));
        }

        if (preg_match('/^[a-zA-Z]:/', $path) === 1) {
            throw new PackageIntegrityException(sprintf('Letra de unidade não permitida no caminho do pacote: "%s".', $path));
        }

        $trimmed = rtrim($path, '/');
        if ($trimmed === '') {
            throw new PackageIntegrityException('Entrada de diretório raiz "/" inválida no pacote.');
        }

        $segments = explode('/', $trimmed);
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw new PackageIntegrityException(sprintf(
                    'Segmento de caminho inválido ("%s") detectado em "%s".',
                    $segment,
                    $path
                ));
            }
        }
    }

    /**
     * Remove recursivamente um diretório de quarentena.
     */
    public static function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            $itemPath = $item->getPathname();
            if ($item->isDir() && !$item->isLink()) {
                @rmdir($itemPath);
            } else {
                @unlink($itemPath);
            }
        }

        @rmdir($dir);
    }
}
