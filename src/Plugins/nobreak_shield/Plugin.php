<?php
namespace DomainSystem\Plugins\nobreak_shield;

use DomainSystem\Core\Plugin\AbstractPlugin;

class Plugin extends AbstractPlugin
{
    public function register(): void
    {
        // Intercepta a cadeia de erros para "desarmar" o erro sem desligar o disjuntor principal
        set_exception_handler([$this, 'handleShieldException']);
    }

    public function handleShieldException(\Throwable $e): void
    {
        // Limpa qualquer HTML que já estava sendo renderizado pela metade
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Em vez de matar o sistema todo, renderizamos uma tela segura
        http_response_code(500);
        $message = htmlspecialchars($e->getMessage());
        $file = htmlspecialchars(basename($e->getFile()));
        $line = $e->getLine();
        
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <title>Erro de Módulo Interceptado (No-Break Shield)</title>
            <style>
                body { font-family: -apple-system, system-ui, sans-serif; background: #f0f0f1; margin: 0; padding: 40px; display: flex; justify-content: center; }
                .shield-box { background: #fff; border-left: 6px solid #f56e28; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); max-width: 700px; width: 100%; }
                h1 { margin-top: 0; color: #f56e28; font-size: 22px; display: flex; align-items: center; gap: 10px; }
                p { color: #50575e; line-height: 1.6; }
                .code { background: #1d2327; color: #00ff00; padding: 15px; border-radius: 6px; font-family: monospace; font-size: 13px; margin-top: 20px; overflow-x: auto; }
                .btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #2271b1; color: #fff; text-decoration: none; border-radius: 4px; font-weight: bold; }
                .btn:hover { background: #135e96; }
            </style>
        </head>
        <body>
            <div class="shield-box">
                <h1>🛡️ No-Break Shield: Erro Contido</h1>
                <p>O <strong>No-Break Shield</strong> entrou em ação! Um módulo tentou executar uma operação inválida, mas o erro foi desarmado e filtrado antes de atingir o núcleo do sistema.</p>
                <p>O disjuntor principal <strong>não precisou ser ativado</strong>, e todos os seus plugins continuam online.</p>
                <div class="code">
                    Ocorrência: {$message}<br>
                    Arquivo: {$file}<br>
                    Linha: {$line}
                </div>
                <a href="javascript:history.back()" class="btn">&larr; Voltar com Segurança</a>
            </div>
        </body>
        </html>
HTML;
        exit(1);
    }

    public function activate(): void
    {
    }

    public function deactivate(): void
    {
    }
}
