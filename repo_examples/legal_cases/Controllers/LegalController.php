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

        $cases = $this->db->table('legal_cases')->get();
        
        return $this->theme->render('admin_legal', ['cases' => $cases], __DIR__ . '/../views');
    }
}


