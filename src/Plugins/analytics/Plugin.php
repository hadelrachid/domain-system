<?php

namespace DomainSystem\Plugins\analytics;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Contracts\OsConnectorInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    public function register(): void {}

    public function osRegister(OsConnectorInterface $os): void
    {
        $os->requireLink('core.db');
        $os->listenHook('router.register');
        $os->listenHook('dashboard.register_widgets');
        $os->listenHook('seo.after_render');
    }

    public function osBoot(OsRuntimeInterface $runtime): void
    {
        // 1. Injeta o tracker no final do HTML
        $runtime->onHook('seo.after_render', function(string &$html) {
            $trackerScript = "
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Registrar visualização de página
                fetch('" . BASE_URL . "/api/analytics/track', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ type: 'pageview', source: window.location.pathname })
                });

                // Registrar cliques em botões trackeados
                document.querySelectorAll('[data-track]').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        let sourceName = this.getAttribute('data-track');
                        fetch('" . BASE_URL . "/api/analytics/track', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ type: 'click', source: sourceName })
                        });
                    });
                });
            });
            </script>";
            
            // Injeta antes de fechar o body
            $html = str_replace('</body>', $trackerScript . "\n</body>", $html);
        });

        // 2. Registrar rota da API
        $runtime->onHook('router.register', function(Router $router) {
            $router->addRoute('POST', '/api/analytics/track', [\DomainSystem\Plugins\analytics\Controllers\TrackerController::class, 'track']);
        });

        // 3. Adicionar Widgets no Dashboard
        try {
            $registry = $this->container->make(\DomainSystem\Core\Registry\DashboardWidgetRegistry::class);
            $db = $this->container->make(\DomainSystem\SystemApps\Database\Connection::class)->getPdo();
            $registry->registerProvider(new \DomainSystem\Plugins\analytics\Widgets\AnalyticsWidgetProvider($db));
        } catch (\Exception $e) {}
    }

    public function activate(): void
    {
        try {
            $schema = $this->container->make(\DomainSystem\SystemApps\Database\Schema\SchemaBuilder::class);
            $schema->create('analytics_events', function ($table) {
                $table->id();
                $table->string('event_type', 50); // pageview, click
                $table->string('event_source', 100); // url or button name
                $table->string('session_hash', 64); // to count unique visitors
                $table->datetime('created_at')->default('CURRENT_TIMESTAMP');
            });
        } catch (\Exception $e) {}
    }
}
