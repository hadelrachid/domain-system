<?php
namespace DomainSystem\Plugins\flex_builder\Controllers;

use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;

class BuilderController
{
    public function index(Request $request): Response
    {
        ob_start();
        require __DIR__ . '/../Views/builder_app.php';
        $content = ob_get_clean();

        // Retorna envelopado no layout do SystemAdmin
        return new Response(apply_filters('admin_layout', $content, 'Construtor Flex'));
    }
}
