<?php

namespace DomainSystem\Plugins\legal_cases;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Contracts\OsConnectorInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Plugins\legal_cases\Controllers\LegalController;
use DomainSystem\Plugins\legal_cases\Repositories\SqliteLegalCaseRepository;
use DomainSystem\Plugins\legal_cases\Contracts\LegalCaseRepositoryInterface;
use DomainSystem\Plugins\legal_cases\Workspace\LawyerWorkspace;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    public function register(): void {}

    // ==========================================================
    // FASE 1: NEGOCIAÇÃO
    // ==========================================================
    public function osRegister(OsConnectorInterface $os): void
    {
        $os->requireLink('core.db');
        $os->requireLink('core.db.schema');
        $os->requireLink('core.identity');
        $os->requireLink('core.theme');

        $os->listenHook('router.register');
        $os->listenHook('workspace.register');
        $os->listenHook('admin.menu');
    }

    // ==========================================================
    // FASE 2: EXECUÇÃO
    // ==========================================================
    public function osBoot(OsRuntimeInterface $runtime): void
    {
        // 1. Registra o Repositório no Container via Link (SOLID: DIP)
        $runtime->singleton(LegalCaseRepositoryInterface::class, function () use ($runtime) {
            return new SqliteLegalCaseRepository($runtime->getLink('core.db'));
        });

        // 2. Registra o Workspace do Advogado
        $runtime->onHook('workspace.register', function ($wm) use ($runtime) {
            $theme = $runtime->getLink('core.theme');
            $wm->registerWorkspace('lawyer', new LawyerWorkspace($theme));
        });

        // 3. Registra as Rotas
        $runtime->onHook('router.register', function (Router $router) {
            $router->addRoute(
                'GET',
                '/admin/legal',
                [LegalController::class, 'index'],
                'legal_cases',
                ['admin', 'lawyer']
            );
        });

        // 4. Adiciona o item no menu administrativo
        $runtime->onHook('admin.menu', function ($menus, $role = 'admin') {
            if (in_array($role, ['admin', 'lawyer'], true)) {
                $menus[] = [
                    'title' => '⚖️ Processos',
                    'url' => '/admin/legal',
                    'icon' => '⚖️'
                ];
            }
            return $menus;
        });
    }

    // ==========================================================
    // FASE 3: ATIVAÇÃO (Roda uma única vez)
    // ==========================================================
    public function activate(OsRuntimeInterface $runtime): void
    {
        $schema = $runtime->getLink('core.db.schema');
        $schema->create('legal_cases', function ($table) {
            $table->id();
            $table->string('client_name');
            $table->string('case_number');
            $table->string('status', 50)->default('Ativo');
            $table->timestamps();
        });

        $db = $runtime->getLink('core.db')->getPdo();
        $count = $db->query("SELECT COUNT(*) FROM legal_cases")->fetchColumn();
        if ($count == 0) {
            $db->exec("INSERT INTO legal_cases (client_name, case_number, status) VALUES 
                ('John Doe', '00123-2026-SP', 'Aguardando Audiência'),
                ('Wayne Enterprises', '00999-2026-SP', 'Em Recurso')");
        }

        $runtime->registerCapability('legal.cases.view', 'Módulo Jurídico');
        $runtime->registerCapability('legal.cases.manage', 'Módulo Jurídico');
    }
}
