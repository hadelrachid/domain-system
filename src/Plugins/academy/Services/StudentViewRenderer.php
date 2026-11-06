<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Services;

use DomainSystem\Core\Contracts\ResponseInterface;
use DomainSystem\Core\Http\Response;
use DomainSystem\Plugins\academy\Contracts\StudentViewRendererInterface;

/**
 * Renderizador Oficial de Telas Públicas do Aluno.
 * Produz respostas HTTP independentes, isoladas de qualquer casca ou middleware administrativo.
 */
class StudentViewRenderer implements StudentViewRendererInterface
{
    private string $viewsPath;

    public function __construct(?string $viewsPath = null)
    {
        $this->viewsPath = $viewsPath ?? dirname(__DIR__) . '/views';
    }

    public function render(string $viewName, array $data = []): ResponseInterface
    {
        $viewFile = rtrim($this->viewsPath, '/\\') . '/' . ltrim($viewName, '/\\') . '.php';
        if (!is_file($viewFile)) {
            return new Response("<h1>Página não encontrada</h1>", 404, [
                'Content-Type' => 'text/html; charset=utf-8'
            ]);
        }

        ob_start();
        extract($data, EXTR_SKIP);
        include $viewFile;
        $content = (string)ob_get_clean();

        return new Response($content, 200, [
            'Content-Type' => 'text/html; charset=utf-8'
        ]);
    }
}
