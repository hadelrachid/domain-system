<?php

namespace DomainSystem\Core\Utils\Archive;

use Exception;
use PharData;

class PharDataExtractor implements ExtractorInterface
{
    public function extract(string $archivePath, string $destinationPath, string $descriptorFile = 'plugin.json'): string
    {
        $phar = new PharData($archivePath);
        
        $hasDescriptor = false;
        $componentDirName = null;

        // Com PharData, arquivos podem estar na raiz ou numa pasta
        foreach ($phar as $file) {
            if ($file->isDir()) {
                $dirName = $file->getFilename();
                if (isset($phar[$dirName . '/' . $descriptorFile])) {
                    $hasDescriptor = true;
                    $componentDirName = $dirName;
                    break;
                }
            }
        }
        
        // Se zipado diretamente (sem pasta raiz)
        if (!$hasDescriptor && isset($phar[$descriptorFile])) {
            $json = file_get_contents($phar[$descriptorFile]->getPathname());
            $data = json_decode($json, true);
            if (isset($data['name'])) {
                $hasDescriptor = true;
                $componentDirName = preg_replace('/[^a-zA-Z0-9]+/', '-', strtolower($data['name']));
                
                @mkdir($destinationPath . '/' . $componentDirName, 0777, true);
                $phar->extractTo($destinationPath . '/' . $componentDirName);
                return $componentDirName;
            }
        }

        if ($hasDescriptor && $componentDirName) {
            $phar->extractTo($destinationPath);
            return $componentDirName;
        }

        throw new Exception("ZIP inválido: Não possui um arquivo $descriptorFile válido no pacote.");
    }
}
