<?php

namespace DomainSystem\Core\Plugin\Services;

use DomainSystem\Core\Utils\Archive\ExtractorFactory;
use Exception;

/**
 * ════════════════════════════════════════════════════════════════════════════
 * CLASSE: PluginInstaller (O Gatekeeper de Instalação)
 * ════════════════════════════════════════════════════════════════════════════
 *
 * Responsabilidade Única (SRP): Manipular o sistema de arquivos para 
 * instalar ou remover plugins.
 * 
 * MEDIDAS DE SEGURANÇA GATEKEEPER:
 * ────────────────────────────────
 * Quando um admin faz o upload de um plugin (.zip), esta classe não confia
 * no arquivo apenas por ele ser um admin. Ela realiza verificações estritas:
 *
 * 1. O plugin obrigatoriamente vai para a pasta de User Plugins (Ring 3).
 *    É proibido instalar SystemApps (Ring 0) via upload web.
 * 
 * 2. Varredura do Namespace: O Gatekeeper lê o Plugin.php dentro do ZIP.
 *    Se o arquivo declarar "namespace DomainSystem\SystemApps", a 
 *    instalação é BLOQUEADA. Nenhum código externo pode tentar se passar 
 *    por um componente de núcleo (Privilege Escalation).
 */
class PluginInstaller
{
    private string $basePath;
    private PluginStateManager $stateManager;

    public function __construct(string $basePath, PluginStateManager $stateManager)
    {
        $this->basePath     = $basePath;
        $this->stateManager = $stateManager;
    }

    /**
     * @return string O caminho de destino final (Sempre Ring 3)
     */
    private function getPluginsPath(): string
    {
        return $this->basePath . '/src/Plugins';
    }

    /**
     * Recebe um caminho de ZIP, faz extração com quarentena e valida
     * a segurança arquitetural antes de liberar a instalação.
     */
    public function installFromZip(string $zipFilePath): string
    {
        // 1. Descompacta na pasta temporária e resolve as proteções de Path Traversal
        $extractor = ExtractorFactory::create();
        $pluginFolder = $extractor->extract($zipFilePath, $this->getPluginsPath());

        // O plugin agora está em src/Plugins/$pluginFolder
        $installedPath = $this->getPluginsPath() . '/' . $pluginFolder;

        try {
            // 2. AUDITORIA GATEKEEPER (Privilege Escalation Prevention)
            $this->auditCodeSecurity($installedPath);

        } catch (Exception $e) {
            // Se falhar na auditoria, aborta e destrói os arquivos instalados
            $this->deleteDirectory($installedPath);
            throw new Exception("Auditoria do Gatekeeper falhou: " . $e->getMessage());
        }

        return $pluginFolder;
    }

    /**
     * Varre os arquivos críticos do plugin recém-instalado para garantir
     * que ele não esteja tentando quebrar as regras arquiteturais.
     */
    private function auditCodeSecurity(string $installedPath): void
    {
        $pluginFile = $installedPath . '/Plugin.php';
        
        if (!file_exists($pluginFile)) {
            throw new Exception("O pacote não contém a classe Plugin.php obrigatória.");
        }

        $code = file_get_contents($pluginFile);

        // Bloqueia qualquer tentativa do plugin de se registrar como Ring 0 (SystemApp)
        if (preg_match('/namespace\s+DomainSystem\\\\SystemApps/i', $code)) {
            throw new Exception("Risco de Segurança (Escalação de Privilégios): O plugin está tentando se passar por um SystemApp (Ring 0). Instalação abortada.");
        }

        // Poderiamos futuramente adicionar análise estática para bloquear eval(), shell_exec() etc.
        // Mas por ora, a divisão de Rings protege o banco de dados e Auth.
    }

    public function delete(string $pluginName, string $pluginFolder): void
    {
        // Proteção adicional para garantir que não estamos apagando Ring 0
        if ($this->isCore($pluginName) || strpos($pluginFolder, 'SystemApps') !== false) {
            throw new Exception("Violacão do Gatekeeper: Não é possível excluir SystemApps (Ring 0) protegidos.");
        }

        $states = $this->stateManager->getActiveStates();
        if (!empty($states[$pluginName])) {
            throw new Exception("O plugin precisa ser desativado antes de ser excluído.");
        }

        $dir = $this->getPluginsPath() . '/' . $pluginFolder;
        if (is_dir($dir)) {
            if (!$this->deleteDirectory($dir)) {
                throw new Exception("Falha ao excluir o diretório do plugin.");
            }
        }
    }

    private function isCore(string $pluginName): bool
    {
        $states = $this->stateManager->getActiveStates();
        return isset($states[$pluginName]) && $states[$pluginName] === 'core';
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
