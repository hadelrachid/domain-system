<?php

namespace DomainSystem\Core\Utils\Archive;

use Exception;
use PharData;

class PharDataExtractor implements ExtractorInterface
{
    public function extract(string $archivePath, string $destinationPath): string
    {
        $phar = new PharData($archivePath);
        
        $hasPluginJson = false;
        $pluginDirName = null;

        // Com PharData, arquivos podem estar na raiz ou numa pasta
        foreach ($phar as $file) {
            if ($file->isDir()) {
                $dirName = $file->getFilename();
                if (isset($phar[$dirName . '/plugin.json'])) {
                    $hasPluginJson = true;
                    $pluginDirName = $dirName;
                    break;
                }
            }
        }
        
        // Se zipado diretamente (sem pasta raiz)
        if (!$hasPluginJson && isset($phar['plugin.json'])) {
            $json = file_get_contents($phar['plugin.json']->getPathname());
            $data = json_decode($json, true);
            if (isset($data['name'])) {
                $hasPluginJson = true;
                $pluginDirName = $data['name'];
                
                @mkdir($destinationPath . '/' . $pluginDirName, 0777, true);
                $phar->extractTo($destinationPath . '/' . $pluginDirName);
                return $pluginDirName;
            }
        }

        if ($hasPluginJson && $pluginDirName) {
            $phar->extractTo($destinationPath);
            return $pluginDirName;
        }

        throw new Exception("ZIP inválido: Não possui um arquivo plugin.json válido no pacote.");
    }
}
