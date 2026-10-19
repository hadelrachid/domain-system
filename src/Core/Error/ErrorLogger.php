<?php

namespace DomainSystem\Core\Error;

use DomainSystem\Core\Contracts\JsonStoreInterface;
use DomainSystem\Core\Storage\AtomicJsonStore;
use Throwable;

class ErrorLogger
{
    private string $logPath;
    private JsonStoreInterface $jsonStore;

    public function __construct(string $logPath, JsonStoreInterface $jsonStore = new AtomicJsonStore())
    {
        $this->logPath = $logPath;
        $this->jsonStore = $jsonStore;
    }

    public function log(Throwable $e): void
    {
        $dir = dirname($this->logPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
            @file_put_contents($dir . '/.htaccess', "Deny from all\n", LOCK_EX);
        }

        $errorData = [
            "timestamp" => date("Y-m-d H:i:s"),
            "type"      => get_class($e),
            "message"   => $e->getMessage(),
            "file"      => $e->getFile(),
            "line"      => $e->getLine(),
            "trace"     => $e->getTraceAsString(),
            "url"       => $_SERVER["REQUEST_URI"] ?? "CLI",
            "method"    => $_SERVER["REQUEST_METHOD"] ?? "CLI",
        ];

        $this->jsonStore->update($this->logPath, static function (array $logs) use ($errorData): array {
            array_unshift($logs, $errorData);
            if (count($logs) > 50) {
                $logs = array_slice($logs, 0, 50);
            }
            return $logs;
        });
    }
}
