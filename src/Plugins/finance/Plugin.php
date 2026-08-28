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

        /** @var EventDispatcher $events */
        $events = $this->container->make(EventDispatcher::class);

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
        $events->addListener('init', function() {
            if (function_exists('add_shortcode')) {
                add_shortcode('finance_summary', [FinanceController::class, 'renderShortcodeSummary'], 'Cards de resumo financeiro (Receitas, Despesas, Saldo).', []);
                add_shortcode('finance_form', [FinanceController::class, 'renderShortcodeForm'], 'Formulário para lançamento de nova receita/despesa.', []);
                add_shortcode('finance_list', [FinanceController::class, 'renderShortcodeList'], 'Tabela de lançamentos financeiros.', ['limit' => 'Máximo de itens exibidos']);
            }
        });
    }

    public function activate(): void
    {
        /** @var \DomainSystem\Plugins\Database\Connection $connection */
        $connection = $this->container->make(\DomainSystem\Plugins\Database\Connection::class);
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
