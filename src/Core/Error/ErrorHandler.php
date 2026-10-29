<?php

declare(strict_types=1);

namespace DomainSystem\Core\Error;

use Throwable;

class ErrorHandler
{
    private ErrorLogger $logger;
    private CircuitBreaker $circuitBreaker;
    private ErrorRenderer $renderer;

    public function __construct(
        ErrorLogger $logger,
        CircuitBreaker $circuitBreaker,
        ErrorRenderer $renderer
    ) {
        $this->logger = $logger;
        $this->circuitBreaker = $circuitBreaker;
        $this->renderer = $renderer;
    }

    public function register(): void
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    public function handleException(Throwable $e): void
    {
        $this->logger->log($e);
        $disarmedPlugin = $this->circuitBreaker->analyzeAndDisarm($e);
        $this->renderer->render($e, $disarmedPlugin);
    }

    public function handleError(int $level, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $level)) {
            return false;
        }

        $isFatalLevel = in_array($level, [
            E_ERROR,
            E_PARSE,
            E_CORE_ERROR,
            E_COMPILE_ERROR,
            E_USER_ERROR,
            E_RECOVERABLE_ERROR
        ], true);

        $isDevelopment = (getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'production')) === 'development';

        if ($isFatalLevel || $isDevelopment) {
            $e = new \ErrorException($message, 0, $level, $file, $line);
            $this->handleException($e);
            return true;
        }

        // Em produção, registra avisos e depreciações no log silenciosamente sem derrubar o sistema
        $this->logger->log(new \ErrorException($message, 0, $level, $file, $line));
        return true;
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $e = new \ErrorException($error["message"], 0, $error["type"], $error["file"], $error["line"]);
            $this->handleException($e);
        }
    }
}
