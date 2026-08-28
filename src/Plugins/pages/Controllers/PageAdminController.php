<?php

namespace DomainSystem\Plugins\pages\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\Database\QueryBuilder;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;

class PageAdminController
{
    private ThemeManager $theme;
    private QueryBuilder $db;

    public function __construct(ThemeManager $theme, QueryBuilder $db)
    {
        $this->theme = $theme;
        $this->db = $db;
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    public function index()
    {
        $pages = $this->db->table('pages')->orderBy('created_at', 'DESC')->get();
        return $this->theme->render('admin_pages', ['pages' => $pages], dirname(__DIR__) . '/views');
    }

    public function create()
    {
        return $this->theme->render('admin_page_form', ['page' => null], dirname(__DIR__) . '/views');
    }

    public function edit(string $id)
    {
        $page = $this->db->table('pages')->where('id', '=', $id)->first();
        if (!$page) {
            return Response::redirect(\BASE_URL . '/admin/pages');
        }
        return $this->theme->render('admin_page_form', ['page' => $page], dirname(__DIR__) . '/views');
    }

    public function store(Request $request)
    {
        $id = $request->input('id');
        $title = $request->input('title');
        $content = $request->input('content', '');
        
        if (empty($title)) {
            $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'O Título é obrigatório.'];
            return Response::redirect(\BASE_URL . '/admin/pages');
        }

        // Gera slug básico
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));

        if ($id) {
            $this->db->table('pages')->where('id', '=', $id)->update([
                'title' => $title,
                'content' => $content
            ]);
            $_SESSION['flash_message'] = ['type' => 'success', 'msg' => 'Página atualizada!'];
        } else {
            // Check slug exists
            $exists = $this->db->table('pages')->where('slug', '=', $slug)->first();
            if ($exists) {
                $slug = $slug . '-' . time();
            }

            $this->db->table('pages')->insert([
                'title' => $title,
                'slug' => $slug,
                'content' => $content
            ]);
            $_SESSION['flash_message'] = ['type' => 'success', 'msg' => 'Página criada!'];
        }

        return Response::redirect(\BASE_URL . '/admin/pages');
    }

    public function delete(string $id)
    {
        $this->db->table('pages')->where('id', '=', $id)->delete();
        $_SESSION['flash_message'] = ['type' => 'success', 'msg' => 'Página excluída com sucesso!'];
        return Response::redirect(\BASE_URL . '/admin/pages');
    }
}
