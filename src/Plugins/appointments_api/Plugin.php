<?php
namespace DomainSystem\Plugins\appointments_api;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Contracts\OsConnectorInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    public function register(): void {}

    // ==========================================
    // 1. FASE DE NEGOCIAÇÃO (OS 2.0)
    // ==========================================
    public function osRegister(OsConnectorInterface $os): void
    {
        $os->listenHook("router.register");
    }

    // ==========================================
    // 2. FASE DE EXECUÇÃO (OS 2.0)
    // ==========================================
    public function osBoot(OsRuntimeInterface $runtime): void
    {
        $runtime->onHook("router.register", function ($router) {
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

