<?php

namespace DomainSystem\Plugins\system_terminal\Controllers;

use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Theme\ThemeManager;

class TerminalController
{
    private ThemeManager $theme;

    public function __construct(ThemeManager $theme)
    {
        $this->theme = $theme;
    }

    public function index(Request $request): Response
    {
        $html = $this->theme->render('terminal', [], __DIR__ . '/../views');
        return new Response($html);
    }

    public function execute(Request $request): Response
    {
        $data = $request->all();
        $command = trim($data['command'] ?? '');
        
        if (empty($command)) {
            return Response::json(['output' => '']);
        }

        // --- Alias Engine (Tradutor de Comandos Amigáveis) ---
        // Exemplo: create dir:financeiro -> mkdir financeiro
        if (preg_match('/^create\s*dir:(.+)$/i', $command, $matches)) {
            $command = (DIRECTORY_SEPARATOR === '\\' ? 'mkdir ' : 'mkdir -p ') . escapeshellarg(trim($matches[1]));
        }
        elseif (preg_match('/^delete\s*dir:(.+)$/i', $command, $matches)) {
            $command = (DIRECTORY_SEPARATOR === '\\' ? 'rmdir /S /Q ' : 'rm -rf ') . escapeshellarg(trim($matches[1]));
        }
        elseif (preg_match('/^create\s*file:(.+)$/i', $command, $matches)) {
            $command = (DIRECTORY_SEPARATOR === '\\' ? 'type nul > ' : 'touch ') . escapeshellarg(trim($matches[1]));
        }
        elseif (preg_match('/^delete\s*file:(.+)$/i', $command, $matches)) {
            $command = (DIRECTORY_SEPARATOR === '\\' ? 'del /F /Q ' : 'rm -f ') . escapeshellarg(trim($matches[1]));
        }
        elseif (preg_match('/^open\s*file:(.+)$/i', $command, $matches)) {
            $command = (DIRECTORY_SEPARATOR === '\\' ? 'type ' : 'cat ') . escapeshellarg(trim($matches[1]));
        }
        // Atalhos do CockPit CLI
        elseif (str_starts_with($command, 'plugin:make') || str_starts_with($command, 'theme:make') || str_starts_with($command, 'db:query') || str_starts_with($command, 'clear:temp')) {
            $command = "php cockpit " . $command;
        }

        // Change directory to the project root (where the cockpit file is)
        $rootDir = realpath(__DIR__ . '/../../../../');
        
        // Execute the command in the root directory and capture stderr as well
        // On Windows and Linux, 2>&1 redirects stderr to stdout.
        $safeCommand = "cd " . escapeshellarg($rootDir) . " && " . $command . " 2>&1";
        
        $output = shell_exec($safeCommand);
        
        if ($output === null) {
            $output = "[Nenhuma saída ou comando não encontrado]\n";
        }

        return Response::json(['output' => $output]);
    }
}
