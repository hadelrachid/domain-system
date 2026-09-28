<?php

namespace DomainSystem\Plugins\clinic_pack\Controllers;

use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Application;

class AdminDashboardController
{
    public function index(Request $request): Response
    {
        $html = "<h1>Dashboard da Clínica</h1><p>Bem-vindo ao centro de comando da clínica. Selecione uma opção no menu lateral para gerenciar pacientes, médicos, financeiro e agendamentos.</p>";
        return new Response($html);
    }

    public function shortcodesCatalog(Request $request): Response
    {
        $app = Application::getInstance();
        $shortcodes = $app->getShortcodeManager()->getRegisteredShortcodes();
        
        $html = "<h1>Catálogo de Shortcodes</h1>";
        $html .= "<p>Estes são os componentes visuais que você pode usar para construir novos temas (CockPits).</p>";
        $html .= "<table class='wp-list-table'><thead><tr><th style='width: 250px;'>Tag</th><th>Descrição</th><th>Atributos Suportados</th><th style='width: 100px; text-align: center;'>Ação</th></tr></thead><tbody>";
        foreach ($shortcodes as $tag => $data) {
            $attrs = [];
            foreach ($data['attributes'] as $attr => $desc) {
                $attrs[] = "<strong>{$attr}</strong>: {$desc}";
            }
            $attrsHtml = implode('<br>', $attrs);
            if (empty($attrsHtml)) $attrsHtml = '<em>Nenhum</em>';
            
            $html .= "<tr>";
            $html .= "<td>";
            $html .= "<code id='sc-{$tag}' style='display:inline-block;'>&#91;{$tag}&#93;</code>";
            $html .= "</td>";
            $html .= "<td>{$data['description']}</td>";
            $html .= "<td>{$attrsHtml}</td>";
            $html .= "<td style='text-align: center; vertical-align: middle;'>";
            $html .= "<button class='btn btn-activate' onclick='navigator.clipboard.writeText(\"[\" + \"{$tag}\" + \"]\"); this.innerText=\"Copiado!\"; setTimeout(() => this.innerText=\"Copiar\", 2000);' style='padding: 6px 12px; font-size: 12px; font-weight: 600; white-space: nowrap; width: 100%; max-width: 90px; text-align: center; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #cbd5e1; cursor: pointer; transition: 0.2s;'>Copiar</button>";
            $html .= "</td>";
            $html .= "</tr>";
        }
        $html .= "</tbody></table>";

        return new Response($html);
    }
}
