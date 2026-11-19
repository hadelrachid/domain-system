<?php

declare(strict_types=1);

namespace Tests\Unit;

use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Http\Responses\JsonResponse;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Plugins\cookie_consent\Middlewares\CookieConsentMiddleware;
use DomainSystem\Plugins\cookie_consent\Plugin;
use DomainSystem\Plugins\cookie_consent\Services\CookieBannerRenderer;
use PHPUnit\Framework\TestCase;

class CookieConsentPluginTest extends TestCase
{
    public function testCookieBannerRendererContainsAllRequiredElements(): void
    {
        $renderer = new CookieBannerRenderer();
        $html = $renderer->render('https://rachidd.com');

        // Botões requeridos
        $this->assertStringContainsString('Aceitar Todos', $html);
        $this->assertStringContainsString('Recusar Opcionais', $html);
        $this->assertStringContainsString('Personalizar', $html);

        // Links de conformidade
        $this->assertStringContainsString('https://rachidd.com/privacidade', $html);
        $this->assertStringContainsString('https://rachidd.com/termos', $html);
        $this->assertStringContainsString('Política de Privacidade', $html);
        $this->assertStringContainsString('Termos de Uso', $html);

        // Conformidade com Google Consent Mode v2
        $this->assertStringContainsString("gtag('consent', 'default'", $html);
        $this->assertStringContainsString('ad_storage', $html);
        $this->assertStringContainsString('analytics_storage', $html);
        $this->assertStringContainsString('ad_user_data', $html);
        $this->assertStringContainsString('ad_personalization', $html);

        // API de revogação/abertura global
        $this->assertStringContainsString('window.RachidDCookieConsent', $html);
        $this->assertStringContainsString('rachidd_cookie_banner', $html);
        $this->assertStringContainsString('rachidd_cookie_modal', $html);
    }

    public function testCookieConsentMiddlewareInjectsBannerInHtmlResponse(): void
    {
        $middleware = new CookieConsentMiddleware();
        $request = new Request([], [], ['REQUEST_URI' => '/loja']);

        $next = function (Request $req) {
            return new Response('<!DOCTYPE html><html><head><title>Loja</title></head><body><main>Catálogo</main></body></html>');
        };

        $response = $middleware->handle($request, $next);
        $content = $response->getContent();

        $this->assertStringContainsString('id="rachidd_cookie_banner"', $content);
        $this->assertStringContainsString('window.RachidDCookieConsent', $content);
        $this->assertStringContainsString('Aceitar Todos', $content);
        $this->assertStringContainsString('Recusar Opcionais', $content);
        // Garante que o banner foi injetado antes de </body>
        $bannerPos = strpos($content, 'id="rachidd_cookie_banner"');
        $bodyClosePos = strpos($content, '</body>');
        $this->assertNotFalse($bannerPos);
        $this->assertNotFalse($bodyClosePos);
        $this->assertLessThan($bodyClosePos, $bannerPos);
    }

    public function testCookieConsentMiddlewareIgnoresAdminAndApiAndJson(): void
    {
        $middleware = new CookieConsentMiddleware();

        // 1. Rota de API
        $apiReq = new Request([], [], ['REQUEST_URI' => '/api/drop/track-demand']);
        $apiRes = $middleware->handle($apiReq, fn () => new JsonResponse(['success' => true]));
        $this->assertStringNotContainsString('rachidd_cookie_banner', (string)$apiRes->getContent());

        // 2. Rota Administrativa
        $adminReq = new Request([], [], ['REQUEST_URI' => '/admin/dropship']);
        $adminRes = $middleware->handle($adminReq, fn () => new Response('<html><body>Admin Panel</body></html>'));
        $this->assertStringNotContainsString('rachidd_cookie_banner', (string)$adminRes->getContent());

        // 3. Rota de Webhook / Checkout Process
        $procReq = new Request([], [], ['REQUEST_URI' => '/checkout/process']);
        $procRes = $middleware->handle($procReq, fn () => new JsonResponse(['paid' => true]));
        $this->assertStringNotContainsString('rachidd_cookie_banner', (string)$procRes->getContent());

        // 4. Tela de Login e Logout do Administrador (/login e /logout)
        $loginReq = new Request([], [], ['REQUEST_URI' => '/login']);
        $loginRes = $middleware->handle($loginReq, fn () => new Response('<!DOCTYPE html><html><body><div class="login-box"><form action="/login" method="post"><input type="password" name="password"/></form></div></body></html>'));
        $this->assertStringNotContainsString('rachidd_cookie_banner', (string)$loginRes->getContent());

        $logoutReq = new Request([], [], ['REQUEST_URI' => '/logout']);
        $logoutRes = $middleware->handle($logoutReq, fn () => new Response('<html><body>Desconectado</body></html>'));
        $this->assertStringNotContainsString('rachidd_cookie_banner', (string)$logoutRes->getContent());

        // 5. Tela com título ou classe de autenticação Cockpit (caso acesse por rota alternativa)
        $cockpitReq = new Request([], [], ['REQUEST_URI' => '/auth']);
        $cockpitRes = $middleware->handle($cockpitReq, fn () => new Response('<!DOCTYPE html><html><head><title>Cockpit - Autenticação</title></head><body><div class="login-box">Form</div></body></html>'));
        $this->assertStringNotContainsString('rachidd_cookie_banner', (string)$cockpitRes->getContent());
    }

    public function testCookieConsentMiddlewareIgnoresAuthenticatedAdminSession(): void
    {
        $session = $this->createMock(\DomainSystem\Core\Contracts\SessionManagerInterface::class);
        $session->method('has')->with('user_id')->willReturn(true);
        $session->method('get')->with('user_role', '')->willReturn('admin');

        $middleware = new CookieConsentMiddleware(null, $session);
        $request = new Request([], [], ['REQUEST_URI' => '/loja']);
        $response = $middleware->handle($request, fn () => new Response('<html><body>Loja</body></html>'));

        $this->assertStringNotContainsString('rachidd_cookie_banner', (string)$response->getContent());
    }

    public function testPluginLifecycleRegistration(): void
    {
        $pluginPath = dirname(__DIR__, 2) . '/src/Plugins/cookie_consent';
        $plugin = new Plugin($pluginPath);
        $this->assertSame('cookie_consent', $plugin->getName());

        $osConnector = $this->createMock(\DomainSystem\Core\Contracts\OsConnectorInterface::class);
        $osConnector->expects($this->exactly(2))
            ->method('listenHook')
            ->with($this->logicalOr(
                $this->equalTo('router.register'),
                $this->equalTo('shortcodes.register')
            ));

        $plugin->osRegister($osConnector);
    }
}
