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
        $command = $data['command'] ?? '';

        if (empty(trim($command))) {
            return Response::json(['output' => '']);
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
