<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\cookie_consent;

use DomainSystem\Core\Contracts\OsConnectorInterface;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;
use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Plugins\cookie_consent\Middlewares\CookieConsentMiddleware;
use DomainSystem\Plugins\cookie_consent\Services\CookieBannerRenderer;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    public function register(): void
    {
    }

    public function osRegister(OsConnectorInterface $os): void
    {
        $os->listenHook('router.register');
        $os->listenHook('shortcodes.register');
    }

    public function osBoot(OsRuntimeInterface $runtime): void
    {
        $runtime->singleton(CookieBannerRenderer::class, function () {
            return new CookieBannerRenderer();
        });

        $runtime->singleton(CookieConsentMiddleware::class, function () use ($runtime) {
            return new CookieConsentMiddleware(
                $runtime->make(CookieBannerRenderer::class)
            );
        });

        // 1. Registra o Middleware global que injeta o banner no HTML
        $runtime->onHook('router.register', function (Router $router) {
            $router->addGlobalMiddleware(CookieConsentMiddleware::class);
        });

        // 2. Registra shortcodes opcionais para páginas de termos e políticas
        $runtime->onHook('shortcodes.register', function ($shortcodeManager) {
            if (is_object($shortcodeManager) && method_exists($shortcodeManager, 'addShortcode')) {
                $shortcodeManager->addShortcode('cookie_preferences_button', function () {
                    return '<button type="button" onclick="window.RachidDCookieConsent && window.RachidDCookieConsent.open()" style="display: inline-flex; align-items: center; gap: 6px; background: rgba(212,175,55,0.15); border: 1px solid rgba(212,175,55,0.4); color: #F0D060; padding: 7px 14px; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer;">🍪 Gerenciar Preferências de Cookies</button>';
                });
            }
        });
    }
}
