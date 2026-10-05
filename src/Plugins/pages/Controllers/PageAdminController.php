<?php

namespace DomainSystem\Plugins\pages\Controllers;

use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Plugins\pages\Contracts\PageRepositoryInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Http\Response;

class PageAdminController
{
    private ThemeManagerInterface $theme;
    private PageRepositoryInterface $pageRepo;

    public function __construct(ThemeManagerInterface $theme, PageRepositoryInterface $pageRepo)
    {
        $this->theme = $theme;
        $this->pageRepo = $pageRepo;
    }

    public function index()
    {
        $pages = $this->pageRepo->getAll();
        return $this->theme->render('admin_pages', ['pages' => $pages], dirname(__DIR__) . '/views');
    }

    public function create()
    {
        $themes = $this->theme->getAvailableThemes();
        return $this->theme->render('admin_page_form', ['page' => null, 'available_themes' => $themes], dirname(__DIR__) . '/views');
    }

    public function edit(string $id)
    {
        $page = $this->pageRepo->findById((int)$id);
        if (!$page) {
            return Response::redirect(\BASE_URL . '/admin/pages');
        }
        $themes = $this->theme->getAvailableThemes();
        return $this->theme->render('admin_page_form', ['page' => $page, 'available_themes' => $themes], dirname(__DIR__) . '/views');
    }

    public function store(Request $request)
    {
        $id = $request->input('id');
        $title = $request->input('title');
        $content = $request->input('content', '');
        $theme = $request->input('theme');
        $template_file = $request->input('template_file');
        
                $manualSlug = $request->input('slug');

        if (empty($title)) {
            $this->session->setFlash('error', 'O Título é obrigatório.');
            return Response::redirect(\BASE_URL . '/admin/pages');
        }

        $slug = !empty($manualSlug) 
            ? strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $manualSlug)))
            : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));

        if ($id) {
            // Verificar colisão de slug na edição
            $exists = $this->pageRepo->findBySlug($slug);
            if ($exists && $exists['id'] != $id) {
                $slug = $slug . '-' . time();
            }

            $this->pageRepo->update((int)$id, [
                'title' => $title,
                'slug' => $slug,
                'content' => $content,
                'theme' => $theme,
                'template_file' => $template_file
            ]);
            $this->session->setFlash('success', 'Página atualizada!');
        } else {
            $exists = $this->pageRepo->findBySlug($slug);
            if ($exists) {
                $slug = $slug . '-' . time();
            }

            $this->pageRepo->create([
                'title' => $title,
                'slug' => $slug,
                'content' => $content,
                'theme' => $theme,
                'template_file' => $template_file
            ]);
            $this->session->setFlash('success', 'Página criada!');
        }

        return Response::redirect(\BASE_URL . '/admin/pages');
    }

    public function getThemeFiles(Request $request)
    {
        $themeName = $request->input('theme');
        if (!$themeName) {
            return new Response(json_encode([]), 200, ['Content-Type' => 'application/json']);
        }
        
        $templates = $this->theme->getAvailableTemplates($themeName);
        $pages = $this->theme->getAvailablePages($themeName);
        
        $data = [
            'templates' => $templates,
            'pages' => $pages
        ];
        return new Response(json_encode($data), 200, ['Content-Type' => 'application/json']);
    }

    public function delete(string $id)
    {
        $this->pageRepo->delete((int)$id);
        $this->session->setFlash('success', 'Página excluída com sucesso!');
        return Response::redirect(\BASE_URL . '/admin/pages');
    }
}
