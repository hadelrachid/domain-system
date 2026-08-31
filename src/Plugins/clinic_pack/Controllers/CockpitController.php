<?php

namespace DomainSystem\Plugins\clinic_pack\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;

class CockpitController
{
    private ThemeManager $theme;

    public function __construct(ThemeManager $theme)
    {
        $this->theme = $theme;
    }

    public function renderDoctor(Request $request): Response
    {
        $html = $this->theme->renderTheme('cockpit_doctor', 'index', ['user_name' => $_SESSION['user_name'] ?? 'Mdico']);
        return new Response($html);
    }

    public function renderReception(Request $request): Response
    {
        $html = $this->theme->renderTheme('cockpit_reception', 'index', ['user_name' => $_SESSION['user_name'] ?? 'Secretaria']);
        return new Response($html);
    }

    public function renderAdminDashboard(Request $request): Response
    {
        $html = "<h1>Dashboard da Clínica</h1><p>Bem-vindo ao centro de comando da clínica. Selecione uma opção no menu lateral para gerenciar pacientes, médicos, financeiro e agendamentos.</p>";
        return new Response($html);
    }

    public function renderShortcodesCatalog(Request $request): Response
    {
        $app = \DomainSystem\Core\Application::getInstance();
        $shortcodes = $app->getShortcodeManager()->getRegisteredShortcodes();
        
        $html = "<h1>Catálogo de Shortcodes</h1>";
        $html .= "<p>Estes são os componentes visuais que você pode usar para construir novos temas (CockPits).</p>";
        $html .= "<table class='wp-list-table'><thead><tr><th style='width: 250px;'>Tag</th><th>Descrição</th><th>Atributos Suportados</th></tr></thead><tbody>";
        foreach ($shortcodes as $tag => $data) {
            $attrs = [];
            foreach ($data['attributes'] as $attr => $desc) {
                $attrs[] = "<strong>{$attr}</strong>: {$desc}";
            }
            $attrsHtml = implode('<br>', $attrs);
            if (empty($attrsHtml)) $attrsHtml = '<em>Nenhum</em>';
            
            $html .= "<tr>";
            $html .= "<td>";
            $html .= "<code id='sc-{$tag}' style='display:inline-block; margin-right: 8px;'>&#91;{$tag}&#93;</code>";
            $html .= "<button class='btn btn-activate' onclick='navigator.clipboard.writeText(\"[\" + \"{$tag}\" + \"]\"); this.innerText=\"Copiado!\"; setTimeout(() => this.innerText=\"Copiar\", 2000);' style='padding: 2px 8px; font-size: 11px; white-space: nowrap;'>Copiar</button>";
            $html .= "</td>";
            $html .= "<td>{$data['description']}</td>";
            $html .= "<td>{$attrsHtml}</td>";
            $html .= "</tr>";
        }
        $html .= "</tbody></table>";

        return new Response($html);
    }
}
