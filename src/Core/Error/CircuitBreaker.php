<?php

namespace DomainSystem\Core\Error;

use Throwable;

class CircuitBreaker
{
    private string $configPath;
    private string $disarmedPath;

    public function __construct(string $configPath, string $disarmedPath)
    {
        $this->configPath = $configPath;
        $this->disarmedPath = $disarmedPath;
    }

    public function analyzeAndDisarm(Throwable $e): ?string
    {
        $file = $e->getFile();
        $trace = $e->getTraceAsString();
        $message = $e->getMessage();
        $pluginName = null;

        // Tenta achar a pasta do plugin causador no arquivo, trace ou mensagem
        if (preg_match("/src[\/\\\\]Plugins[\/\\\\]([a-zA-Z0-9_-]+)/i", $file, $matches)) {
            $pluginName = $matches[1];
        } elseif (preg_match("/src[\/\\\\]Plugins[\/\\\\]([a-zA-Z0-9_-]+)/i", $trace, $matches)) {
            $pluginName = $matches[1];
        } elseif (preg_match("/src[\/\\\\]Plugins[\/\\\\]([a-zA-Z0-9_-]+)/i", $message, $matches)) {
            $pluginName = $matches[1];
        }

        // Se encontrou um plugin e nao for o proprio Monitor
        if ($pluginName && $pluginName !== "SystemMonitor") {
            if (file_exists($this->configPath)) {
                $states = json_decode(file_get_contents($this->configPath), true) ?: [];
                if (isset($states[$pluginName]) && $states[$pluginName] === true) {
                    $states[$pluginName] = false; // Desarma o disjuntor (desativa plugin)
                    file_put_contents($this->configPath, json_encode($states, JSON_PRETTY_PRINT));
                    
                    // Grava no arquivo disarmed.json para exibir alerta no painel de plugins
                    $disarmed = file_exists($this->disarmedPath) ? (json_decode(file_get_contents($this->disarmedPath), true) ?: []) : [];
                    $disarmed[$pluginName] = date('Y-m-d H:i:s');
                    file_put_contents($this->disarmedPath, json_encode($disarmed, JSON_PRETTY_PRINT));

                    return $pluginName;
                }
            }
        }
        return null;
    }
}
