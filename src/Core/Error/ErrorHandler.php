<?php
namespace DomainSystem\Core\Error;

use Throwable;

class ErrorHandler
{
    private string $logPath;

    public function __construct(string $logPath)
    {
        $this->logPath = $logPath;
    }

    public function register(): void
    {
        error_reporting(E_ALL);
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    public function handleError($level, $message, $file = '', $line = 0): bool
    {
        if (error_reporting() & $level) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
        return false;
    }

    public function handleException(Throwable $e): void
    {
        $this->logError($e);
        $this->renderErrorPage($e);
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $e = new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
            $this->handleException($e);
        }
    }

    private function logError(Throwable $e): void
    {
        if (!is_dir(dirname($this->logPath))) {
            mkdir(dirname($this->logPath), 0777, true);
        }
        
        $errorData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'url' => $_SERVER['REQUEST_URI'] ?? 'CLI',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI'
        ];

        // limit log to last 50 errors
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

    private function renderErrorPage(Throwable $e): void
    {
        if (!headers_sent()) {
            http_response_code(500);
            header("Content-Type: text/html; charset=UTF-8");
        }
        
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($isAjax) {
            echo json_encode(['error' => true, 'message' => 'Erro interno no servidor.', 'details' => $e->getMessage()]);
            exit;
        }

        $message = htmlspecialchars($e->getMessage());
        $file = htmlspecialchars($e->getFile());
        $line = $e->getLine();
        
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Erro Crítico - DomainSystem</title>
            <style>
                body { font-family: -apple-system, system-ui, sans-serif; background: #f0f2f5; color: #1d2327; margin:0; padding: 40px 20px; display: flex; justify-content: center; }
                .container { max-width: 800px; width: 100%; background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-top: 6px solid #d63638; }
                h1 { color: #d63638; margin-top: 0; font-size: 24px; display: flex; align-items: center; gap: 10px; }
                .description { font-size: 16px; color: #50575e; margin-bottom: 25px; line-height: 1.5; }
                .details { background: #f6f7f7; padding: 20px; border-radius: 6px; font-family: Consolas, monospace; overflow-x: auto; font-size: 14px; line-height: 1.6; border: 1px solid #dcdcde; }
                .highlight { color: #2271b1; font-weight: 600; }
                .footer { margin-top:30px; font-size:13px; color:#8c8f94; border-top: 1px solid #dcdcde; padding-top: 20px; }
                .btn { display:inline-block; margin-top:20px; padding:10px 24px; background:#2271b1; color:#fff; text-decoration:none; border-radius:3px; font-weight: 500; transition: background 0.2s; }
                .btn:hover { background: #135e96; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    Erro Crítico Rastreável
                </h1>
                <p class="description">O motor <strong>Domain-System</strong> interceptou uma falha fatal que impediu a execução desta tela. O erro foi rastreado e isolado para não afetar o restante do sistema.</p>
                
                <div class="details">
                    <div style="margin-bottom: 8px;"><span class="highlight">Ocorrência:</span> {$message}</div>
                    <div style="margin-bottom: 8px;"><span class="highlight">Arquivo:</span> {$file}</div>
                    <div><span class="highlight">Linha:</span> {$line}</div>
                </div>
                
                <a href="javascript:history.back()" class="btn">&larr; Voltar com Segurança</a>
                
                <div class="footer">
                    Este erro foi gravado no Log de Supervisão. Apenas desenvolvedores ou administradores podem visualizar o diagnóstico completo no Painel Admin.
                </div>
            </div>
        </body>
        </html>
        HTML;
        exit;
    }
}

