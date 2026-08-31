<?php

namespace DomainSystem\Plugins\ApiGateway;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Plugins\ApiGateway\Middleware\ApiAuthMiddleware;
use DomainSystem\Plugins\ApiGateway\Controllers\WebhookController;

class Plugin extends AbstractPlugin
{
    public function register(): void
    {
        $events = $this->events();

        $events->addListener("router.before_dispatch", function(string $uri) {
            if (str_starts_with($uri, "/api/")) {
                $middleware = new ApiAuthMiddleware();
                $request = $this->container->make(\DomainSystem\Core\Http\Request::class);
                $middleware->handle($uri, $request);
            }
        }, 100);

        $events->addListener("router.register", function(Router $router) {
            $router->addRoute("POST", "/api/v1/webhooks/whatsapp", [WebhookController::class, "handleWhatsApp"]);
        });
    }
}

