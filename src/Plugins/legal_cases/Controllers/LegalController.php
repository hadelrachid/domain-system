<?php
namespace DomainSystem\Plugins\legal_cases\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\Database\QueryBuilder;

class LegalController
{
    private ThemeManager $theme;
    private QueryBuilder $db;

    public function __construct(ThemeManager $theme, QueryBuilder $db)
    {
        $this->theme = $theme;
        $this->db = $db;
    }

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (strtolower($_SESSION['user_role'] ?? '') !== 'lawyer') {
            die("Acesso Restrito: Apenas Advogados podem acessar este módulo.");
        }

        $cases = $this->db->table('legal_cases')->get();
        
        return $this->theme->render('admin_legal', ['cases' => $cases], __DIR__ . '/../views');
    }
}


