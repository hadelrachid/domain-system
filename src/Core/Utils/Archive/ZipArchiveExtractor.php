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
        
        // Se zipado diretamente (sem pasta raiz)
        if (!$hasDescriptor) {
            $idx = $zip->locateName($descriptorFile);
            if ($idx !== false) {
                $json = $zip->getFromIndex($idx);
                $data = json_decode($json, true);
                if (isset($data['name'])) {
                    $hasDescriptor = true;
                    $componentDirName = preg_replace('/[^a-zA-Z0-9]+/', '-', strtolower($data['name']));
                    
                    @mkdir($destinationPath . '/' . $componentDirName, 0777, true);
                    $zip->extractTo($destinationPath . '/' . $componentDirName);
                    $zip->close();
                    return $componentDirName;
                }
            }
        }

        if (!$hasDescriptor || !$componentDirName) {
            $zip->close();
            throw new Exception("ZIP inválido: Não possui um arquivo $descriptorFile válido no pacote.");
        }

        $zip->extractTo($destinationPath);
        $zip->close();

        return $componentDirName;
    }
}
