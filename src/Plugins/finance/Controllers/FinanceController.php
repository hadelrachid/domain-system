<?php

namespace DomainSystem\Plugins\finance\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\Database\QueryBuilder;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;

class FinanceController
{
    private ThemeManager $theme;
    private QueryBuilder $db;

    public function __construct(ThemeManager $theme, QueryBuilder $db)
    {
        $this->theme = $theme;
        $this->db = $db;
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function index()
    {
        $transactions = $this->db->table('financial_transactions')->orderBy('due_date', 'DESC')->get();
        
        $totals = $this->calculateTotals($transactions);

        return $this->theme->render('admin_finance', [
            'transactions' => $transactions,
            'totals' => $totals
        ], dirname(__DIR__) . '/views');
    }

    public function store(Request $request)
    {
        $type = $request->input('type');
        $amount = (float) str_replace(['R$', '.', ','], ['', '', '.'], $request->input('amount'));
        $description = $request->input('description');
        $due_date = $request->input('due_date');
        $status = $request->input('status', 'PENDING');
        
        if (empty($type) || empty($amount) || empty($description) || empty($due_date)) {
            $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Preencha os campos obrigatórios.'];
            return Response::redirect(\BASE_URL . '/admin/finance');
        }

        $this->db->table('financial_transactions')->insert([
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
            'due_date' => $due_date,
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $_SESSION['flash_message'] = ['type' => 'success', 'msg' => 'Transação registrada com sucesso.'];
        return Response::redirect(\BASE_URL . '/admin/finance');
    }

    public function updateStatus(Request $request)
    {
        $id = $request->input('id');
        $status = $request->input('status');
        
        if ($id && $status) {
            $this->db->table('financial_transactions')->where('id', '=', $id)->update(['status' => $status]);
            $_SESSION['flash_message'] = ['type' => 'success', 'msg' => 'Status atualizado com sucesso.'];
        }
        
        return Response::redirect(\BASE_URL . '/admin/finance');
    }

    private function calculateTotals(array $transactions): array
    {
        $income = 0;
        $expense = 0;
        
        foreach ($transactions as $t) {
            if ($t['status'] === 'PAID') {
                if ($t['type'] === 'INCOME') $income += $t['amount'];
                if ($t['type'] === 'EXPENSE') $expense += $t['amount'];
            }
        }
        
        return [
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense
        ];
    }

    // SHORTCODES

    public function renderShortcodeSummary($attributes = [])
    {
        $transactions = $this->db->table('financial_transactions')->get();
        $totals = $this->calculateTotals($transactions);
        
        ob_start();
        include dirname(__DIR__) . '/views/partials/summary_cards.php';
        return ob_get_clean();
    }

    public function renderShortcodeForm($attributes = [])
    {
        ob_start();
        include dirname(__DIR__) . '/views/partials/transaction_form.php';
        return ob_get_clean();
    }

    public function renderShortcodeList($attributes = [])
    {
        $limit = $attributes['limit'] ?? null;
        $query = $this->db->table('financial_transactions')->orderBy('due_date', 'DESC');
        if ($limit) $query->limit((int)$limit);
        
        $transactions = $query->get();
        
        ob_start();
        include dirname(__DIR__) . '/views/partials/transaction_list.php';
        return ob_get_clean();
    }
}
