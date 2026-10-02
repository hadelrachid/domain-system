<?php

namespace DomainSystem\Core\Utils\Archive;

use Exception;
use ZipArchive;

/**
 * ════════════════════════════════════════════════════════════════════════════
 * CLASSE: ZipArchiveExtractor (Com Proteção Zip-Slip)
 * ════════════════════════════════════════════════════════════════════════════
 *
 * Responsável por extrair de forma segura pacotes ZIP.
 * 
 * MEDIDAS DE SEGURANÇA:
 * 1. Previne "Zip-Slip" (Arquivos maliciosos com nomes como "../../shell.php").
 * 2. Extrai tudo para uma área de Quarentena (temp) antes da movimentação.
 * 3. Garante que apenas a pasta do componente principal seja copiada.
 */
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

        // 1. ANÁLISE DO PACOTE E PREVENÇÃO DE ZIP-SLIP
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            
            // Segurança: Verifica ataque de Path Traversal (Zip-Slip)
            if (strpos($filename, '../') !== false || strpos($filename, '..\\') !== false) {
                $zip->close();
                throw new Exception("Vulnerabilidade Crítica (Zip-Slip): O pacote contém caminhos maliciosos.");
            }

            if (preg_match('#^([^/]+)/' . $escapedDescriptor . '$#', $filename, $matches)) {
                $hasDescriptor = true;
                $componentDirName = $matches[1];
            }
        }
        
        // 2. PREPARAÇÃO DA QUARENTENA
        $tempDir = dirname($destinationPath, 2) . '/temp/quarentena_zip_' . uniqid();
        @mkdir($tempDir, 0777, true);

        // Se o ZIP não tem pasta raiz (os arquivos estão soltos)
        if (!$hasDescriptor) {
            $idx = $zip->locateName($descriptorFile);
            if ($idx !== false) {
                $json = $zip->getFromIndex($idx);
                $data = json_decode($json, true);
                if (isset($data['name'])) {
                    $hasDescriptor = true;
                    // Padroniza o nome da pasta com base no plugin.json
                    $componentDirName = preg_replace('/[^a-zA-Z0-9_-]+/', '-', strtolower($data['name']));
                    
                    $this->safeExtract($zip, $tempDir);
                    $zip->close();
                    
                    $this->moveToDestination($tempDir, $destinationPath, $componentDirName, $descriptorFile);
                    return $componentDirName;
                }
            }
        }

        if (!$hasDescriptor || !$componentDirName) {
            $zip->close();
            if (is_dir($tempDir)) $this->deleteDirectory($tempDir);
            throw new Exception("ZIP inválido: Não possui o arquivo manifesto ($descriptorFile) no pacote.");
        }

        // 3. EXTRAÇÃO PARA QUARENTENA
        $this->safeExtract($zip, $tempDir);
        $zip->close();

        // 4. TRANSFERÊNCIA DA QUARENTENA PARA A PASTA FINAL
        $sourcePath = $tempDir . '/' . $componentDirName;
        $this->moveToDestination($sourcePath, $destinationPath, $componentDirName, $descriptorFile);
        $this->deleteDirectory($tempDir);

        return $componentDirName;
    }

    /**
     * Extrai os arquivos verificando a sanidade do caminho final (Defesa em Profundidade)
     */
    private function safeExtract(ZipArchive $zip, string $tempDir): void
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            $targetPath = $tempDir . DIRECTORY_SEPARATOR . $filename;
            
            // Defesa 2 contra Zip-Slip: Verifica se o caminho real após resolver continua dentro do tempDir
            if (substr(realpath(dirname($targetPath)), 0, strlen(realpath($tempDir))) !== realpath($tempDir) && realpath(dirname($targetPath)) !== false) {
                 continue; // Arquivo tentando escapar da quarentena, ignorar.
            }
        }
        
        $zip->extractTo($tempDir);
    }

    private function moveToDestination(string $sourcePath, string $destinationPath, string $componentDirName, string $descriptorFile): void
    {
        $targetPath = $destinationPath . '/' . $componentDirName;
        
        // Proteção Ring 0 (Segurança Básica - o Installer fará validações mais profundas)
        if (file_exists($targetPath . '/' . $descriptorFile)) {
            $meta = json_decode(file_get_contents($targetPath . '/' . $descriptorFile), true);
            if (!empty($meta['core'])) {
                $this->deleteDirectory($sourcePath);
                throw new Exception("Bloqueio de Segurança: Não é permitido sobrescrever um SystemApp protegido via upload ZIP.");
            }
        }

        if (file_exists($targetPath)) $this->deleteDirectory($targetPath);
        rename($sourcePath, $targetPath);
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
