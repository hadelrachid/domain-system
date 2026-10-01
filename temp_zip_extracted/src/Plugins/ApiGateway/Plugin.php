<?php

namespace DomainSystem\Plugins\ApiGateway;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Plugins\ApiGateway\Middleware\ApiAuthMiddleware;
use DomainSystem\Plugins\ApiGateway\Controllers\WebhookController;
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
        $os->listenHook("router.before_dispatch");
        $os->listenHook("router.register");
    }

    // ==========================================
    // 2. FASE DE EXECUÇÃO (OS 2.0)
    // ==========================================
    public function osBoot(OsRuntimeInterface $runtime): void
    {
        $runtime->onHook("router.before_dispatch", function(string $uri) {
            if (str_starts_with($uri, "/api/")) {
                $middleware = new ApiAuthMiddleware();
                $request = $this->container->make(\DomainSystem\Core\Http\Request::class);
                $middleware->handle($uri, $request);
            }
        }, 100);

        $runtime->onHook("router.register", function(Router $router) {
            $router->addRoute("POST", "/api/v1/webhooks/whatsapp", [WebhookController::class, "handleWhatsApp"]);
        });
    }
}

