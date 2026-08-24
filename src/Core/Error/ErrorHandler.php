<?php
namespace DomainSystem\Core\Error;

use Throwable;

class ErrorHandler
{
    private string $logPath;
    private string $configPath;

    public function __construct(string $logPath)
    {
        $this->logPath = $logPath;
        $this->configPath = dirname($this->logPath, 2) . "/config/plugins.json";
    }

    public function register(): void
    {
        error_reporting(E_ALL);
        set_error_handler([$this, "handleError"]);
        set_exception_handler([$this, "handleException"]);
        register_shutdown_function([$this, "handleShutdown"]);
    }

    public function handleError($level, $message, $file = "", $line = 0): bool
    {
        if (error_reporting() & $level) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
        return false;
    }

    public function handleException(Throwable $e): void
    {
        $this->logError($e);
        $disarmedPlugin = $this->applyCircuitBreaker($e);
        $this->renderErrorPage($e, $disarmedPlugin);
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error !== null && in_array($error["type"], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $e = new \ErrorException($error["message"], 0, $error["type"], $error["file"], $error["line"]);
            $this->handleException($e);
        }
    }

    private function logError(Throwable $e): void
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

    private function applyCircuitBreaker(Throwable $e): ?string
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
                    $disarmedPath = dirname($this->logPath) . '/disarmed.json';
                    $disarmed = file_exists($disarmedPath) ? (json_decode(file_get_contents($disarmedPath), true) ?: []) : [];
                    $disarmed[$pluginName] = date('Y-m-d H:i:s');
                    file_put_contents($disarmedPath, json_encode($disarmed));

                    return $pluginName;
                }
            }
        }
        return null;
    }

    private function renderErrorPage(Throwable $e, ?string $disarmedPlugin): void
    {
        if (!headers_sent()) {
            http_response_code(500);
            header("Content-Type: text/html; charset=UTF-8");
        }
        
        $isAjax = !empty($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest";
        if ($isAjax) {
            echo json_encode(["error" => true, "message" => "Erro interno.", "disarmed" => $disarmedPlugin, "details" => $e->getMessage()]);
            exit;
        }

        $message = htmlspecialchars($e->getMessage());
        $file = htmlspecialchars($e->getFile());
        $line = $e->getLine();

        // Limpa buffers abertos para garantir que nao vaze layout quebrado (ex: menus pela metade)
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $baseUrl = defined("BASE_URL") ? BASE_URL : (dirname($_SERVER["SCRIPT_NAME"] === "/" ? "" : dirname($_SERVER["SCRIPT_NAME"])));
        $baseUrl = rtrim(str_replace("\\", "/", $baseUrl), "/");
        if (empty($baseUrl)) $baseUrl = "/";

        $disarmedHtml = "";
        if ($disarmedPlugin) {
            $disarmedHtml = "<div style=\"background: #fff3cd; color: #856404; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 5px solid #ffeeba;\">
                <strong>⚡ DISJUNTOR ATIVADO:</strong> O plugin <code>{$disarmedPlugin}</code> causou uma sobrecarga no sistema e foi <b>desconectado automaticamente</b> para salvar o núcleo. O sistema já pode ser reiniciado com segurança.
            </div>";
        }

        echo <<<HTML
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Domain-System - Proteção de Núcleo</title>
            <style>
                body { font-family: -apple-system, system-ui, sans-serif; background: #1d2327; color: #f0f0f1; margin:0; padding: 40px 20px; display: flex; justify-content: center; }
                .container { max-width: 800px; width: 100%; background: #fff; color: #1d2327; padding: 40px; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); border-top: 6px solid #d63638; }
                h1 { color: #d63638; margin-top: 0; font-size: 24px; display: flex; align-items: center; gap: 10px; }
                .description { font-size: 16px; color: #50575e; margin-bottom: 25px; line-height: 1.5; }
                .details { background: #f6f7f7; padding: 20px; border-radius: 6px; font-family: Consolas, monospace; overflow-x: auto; font-size: 14px; line-height: 1.6; border: 1px solid #dcdcde; margin-bottom: 20px;}
                .highlight { color: #2271b1; font-weight: 600; }
                .footer { margin-top:30px; font-size:13px; color:#8c8f94; border-top: 1px solid #dcdcde; padding-top: 20px; }
                .btn { display:inline-block; margin-top:10px; padding:12px 24px; background:#2271b1; color:#fff; text-decoration:none; border-radius:3px; font-weight: 600; transition: background 0.2s; font-size: 15px; }
                .btn:hover { background: #135e96; }
                .btn-success { background: #00a32a; }
                .btn-success:hover { background: #008a20; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    Falha de Núcleo Interceptada
                </h1>
                
                {$disarmedHtml}
                
                <p class="description">O erro foi isolado e registrado no painel de Supervisão (Erros).</p>
                
                <div class="details">
                    <div style="margin-bottom: 8px;"><span class="highlight">Ocorrência:</span> {$message}</div>
                    <div style="margin-bottom: 8px;"><span class="highlight">Arquivo:</span> {$file}</div>
                    <div><span class="highlight">Linha:</span> {$line}</div>
                </div>
                
                <a href="{$baseUrl}/admin" class="btn btn-success">🔄 Recarregar Sistema Seguro</a>
                
                <div class="footer">
                    Apenas desenvolvedores ou administradores podem visualizar o diagnóstico completo no Painel Admin.
                </div>
            </div>
        </body>
        </html>
        HTML;
        exit;
    }
}

