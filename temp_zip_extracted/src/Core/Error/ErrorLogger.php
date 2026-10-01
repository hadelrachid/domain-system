<?php

namespace DomainSystem\Core\Error;

use Throwable;

class ErrorLogger
{
    private string $logPath;

    public function __construct(string $logPath)
    {
        $this->logPath = $logPath;
    }

    public function log(Throwable $e): void
    {
        $dir = dirname($this->logPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
            // Protege a pasta contra acesso via navegador web
            file_put_contents($dir . '/.htaccess', "Deny from all\n");
        }
        
        $errorData = [
            "timestamp" => date("Y-m-d H:i:s"),
            "type" => get_class($e),
            "message" => $e->getMessage(),
            "file" => $e->getFile(),
            "line" => $e->getLine(),
            "trace" => $e->getTraceAsString(),
            "url" => $_SERVER["REQUEST_URI"] ?? "CLI",
            "method" => $_SERVER["REQUEST_METHOD"] ?? "CLI"
        ];

        $logs = [];
        if (file_exists($this->logPath)) {
            $content = file_get_contents($this->logPath);
            $logs = json_decode($content, true) ?: [];
        }
        
        array_unshift($logs, $errorData);
        if (count($logs) > 50) {
            $logs = array_slice($logs, 0, 50);
        }
        
        file_put_contents($this->logPath, json_encode($logs, JSON_PRETTY_PRINT));
    }
}
