<?php

namespace DomainSystem\Core\Utils\Archive;

use Exception;
use ZipArchive;

class ZipArchiveExtractor implements ExtractorInterface
{
    public function extract(string $archivePath, string $destinationPath): string
    {
        $zip = new ZipArchive();
        
        if ($zip->open($archivePath) !== TRUE) {
            throw new Exception("Não foi possível abrir o arquivo ZIP com ZipArchive.");
        }

        $hasPluginJson = false;
        $pluginDirName = null;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if (preg_match('#^([^/]+)/plugin\.json$#', $filename, $matches)) {
                $hasPluginJson = true;
                $pluginDirName = $matches[1];
                break;
            }
        }

        if (!$hasPluginJson || !$pluginDirName) {
            $zip->close();
            throw new Exception("ZIP inválido: Não possui um arquivo plugin.json válido no pacote.");
        }

        $zip->extractTo($destinationPath);
        $zip->close();

        return $pluginDirName;
    }
}
