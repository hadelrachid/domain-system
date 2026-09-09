<?php
namespace DomainSystem\Plugins\appointments_api;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Http\Response;

class Plugin extends AbstractPlugin
{
    public function register(): void
    {
        $this->events()->addListener("router.register", function ($router) {
            // Rota publica de teste para verificar a conectividade
            $router->addRoute("GET", "/api/v1/ping", function () {
                header("Content-Type: application/json");
                header("Access-Control-Allow-Origin: *");
                echo json_encode([
                    "status" => "success",
                    "message" => "Pong! Conexao estabelecida com o Domain-System! O Kernel esta vivo.",
                    "domain" => $_SERVER["HTTP_HOST"] ?? "localhost"
                ]);
                exit;
            }, "appointments_api");
        });
    }

    public function activate(): void {}
    public function deactivate(): void {}
}

