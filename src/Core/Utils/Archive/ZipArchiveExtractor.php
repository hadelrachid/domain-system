<?php

namespace DomainSystem\Core\Utils\Archive;

use Exception;
use ZipArchive;

class ZipArchiveExtractor implements ExtractorInterface
{
    public function extract(string $archivePath, string $destinationPath, string $descriptorFile = 'plugin.json'): string
    {
        $zip = new ZipArchive();
        
        if ($zip->open($archivePath) !== TRUE) {
            throw new Exception("Não foi possível abrir o arquivo ZIP com ZipArchive.");
        }

        $hasDescriptor = false;
        $componentDirName = null;
        
        $escapedDescriptor = preg_quote($descriptorFile, '#');

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if (preg_match('#^([^/]+)/' . $escapedDescriptor . '$#', $filename, $matches)) {
                $hasDescriptor = true;
                $componentDirName = $matches[1];
                break;
            }
        }
        
        $tempDir = dirname($destinationPath, 2) . '/temp/zip_' . uniqid();
        @mkdir($tempDir, 0777, true);

        // Se zipado diretamente (sem pasta raiz)
        if (!$hasDescriptor) {
            $idx = $zip->locateName($descriptorFile);
            if ($idx !== false) {
                $json = $zip->getFromIndex($idx);
                $data = json_decode($json, true);
                if (isset($data['name'])) {
                    $hasDescriptor = true;
                    $componentDirName = preg_replace('/[^a-zA-Z0-9]+/', '-', strtolower($data['name']));
                    
                    $zip->extractTo($tempDir);
                    $zip->close();
                    
                    $targetPath = $destinationPath . '/' . $componentDirName;
                    
                    // Prevenir sobrescrita de plugins core vitais
                    if (file_exists($targetPath . '/' . $descriptorFile)) {
                        $meta = json_decode(file_get_contents($targetPath . '/' . $descriptorFile), true);
                        if (!empty($meta['core'])) {
                            $this->deleteDirectory($tempDir);
                            throw new Exception("Segurança: Não é possível sobrescrever um plugin Core do sistema via upload.");
                        }
                    }

                    if (file_exists($targetPath)) $this->deleteDirectory($targetPath);
                    rename($tempDir, $targetPath);
                    return $componentDirName;
                }
            }
        }

        if (!$hasDescriptor || !$componentDirName) {
            $zip->close();
            if (is_dir($tempDir)) $this->deleteDirectory($tempDir);
            throw new Exception("ZIP inválido: Não possui um arquivo $descriptorFile válido no pacote.");
        }

        $zip->extractTo($tempDir);
        $zip->close();

        // Agora movemos APENAS a pasta do componente, ignorando o resto do lixo do ZIP
        $sourcePath = $tempDir . '/' . $componentDirName;
        $targetPath = $destinationPath . '/' . $componentDirName;

        if (file_exists($targetPath . '/' . $descriptorFile)) {
            $meta = json_decode(file_get_contents($targetPath . '/' . $descriptorFile), true);
            if (!empty($meta['core'])) {
                $this->deleteDirectory($tempDir);
                throw new Exception("Segurança: Não é possível sobrescrever um plugin Core do sistema via upload.");
            }
        }

        if (file_exists($targetPath)) $this->deleteDirectory($targetPath);
        rename($sourcePath, $targetPath);
        $this->deleteDirectory($tempDir);

        return $componentDirName;
    }

    private function deleteDirectory(string $dir): bool
    {
        if (!file_exists($dir)) return true;
        if (!is_dir($dir)) return unlink($dir);
        
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') continue;
            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false;
        }
        return rmdir($dir);
    }
}
