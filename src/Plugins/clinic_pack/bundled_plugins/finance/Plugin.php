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
            \DomainSystem\Plugins\finance\Repositories\SqliteFinanceRepository::class
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
        /** @var \DomainSystem\Plugins\Database\Connection $connection */
        $connection = $this->db();
        $db = $connection->getPdo();
        
        $db->exec("
            CREATE TABLE IF NOT EXISTS financial_transactions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                type VARCHAR(20) NOT NULL, -- 'INCOME' ou 'EXPENSE'
                amount DECIMAL(10, 2) NOT NULL,
                description TEXT NOT NULL,
                due_date DATE NOT NULL,
                status VARCHAR(20) DEFAULT 'PENDING', -- 'PENDING' ou 'PAID'
                patient_id INTEGER NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(patient_id) REFERENCES patients(id)
            )
        ");
    }
}
