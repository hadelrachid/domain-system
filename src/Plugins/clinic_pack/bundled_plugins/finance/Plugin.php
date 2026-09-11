<?php

namespace DomainSystem\Plugins\finance;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Events\EventDispatcher;
use DomainSystem\Plugins\finance\Controllers\FinanceController;

class Plugin extends AbstractPlugin
{
    public function register(): void
    {
        $this->container->bind(
            \DomainSystem\Plugins\finance\Contracts\FinanceRepositoryInterface::class,
            \DomainSystem\Plugins\finance\Repositories\FinanceRepository::class
        );

        /** @var EventDispatcher $events */
        $events = $this->events();

        // Menu
        $events->addListener('admin.menu', function($menus, $role = 'admin') {
            if ($role === 'admin' || $role === 'manager') {
                $menus[] = [
                    'title' => 'Financeiro',
                    'url' => '/admin/finance',
                    'icon' => '💰'
                ];
            }
            return $menus;
        });

        // Rotas
        $events->addListener('router.register', function(Router $router) {
            $router->addRoute('GET', '/admin/finance', [FinanceController::class, 'index'], 'finance', ['admin']);
            $router->addRoute('POST', '/admin/finance/store', [FinanceController::class, 'store'], 'finance', ['admin']);
            $router->addRoute('POST', '/admin/finance/status', [FinanceController::class, 'updateStatus'], 'finance', ['admin']);
        });

        // Shortcodes
        $events->addListener('shortcodes.register', function(\DomainSystem\Core\Theme\ShortcodeManager $shortcodes) {
            $shortcodes->add('finance_summary', [FinanceController::class, 'renderShortcodeSummary'], 'Cards de resumo financeiro (Receitas, Despesas, Saldo).', []);
            $shortcodes->add('finance_form', [FinanceController::class, 'renderShortcodeForm'], 'Formulário para lançamento de nova receita/despesa.', []);
            $shortcodes->add('finance_list', [FinanceController::class, 'renderShortcodeList'], 'Tabela de lançamentos financeiros.', ['limit' => 'Máximo de itens exibidos']);
        });
    }

    public function activate(): void
    {
        $schema = $this->container->make(\DomainSystem\Plugins\Database\Schema\SchemaBuilder::class);
        $schema->create('financial_transactions', function ($table) {
            $table->id();
            $table->string('type', 20); // 'INCOME' ou 'EXPENSE'
            $table->decimal('amount', 10, 2);
            $table->text('description');
            $table->date('due_date');
            $table->string('status', 20)->default('PENDING'); // 'PENDING' ou 'PAID'
            $table->integer('patient_id')->nullable();
            $table->datetime('created_at')->nullable()->default('CURRENT_TIMESTAMP');
            $table->foreign('patient_id', 'id', 'patients');
        });
    }
}
