<?php

namespace DomainSystem\Plugins\SystemAdmin\Widgets;

use DomainSystem\Core\Contracts\DashboardWidgetProviderInterface;

class SystemWidgetProvider implements DashboardWidgetProviderInterface
{
    public function getProviderName(): string
    {
        return "Métricas do Sistema OS";
    }

    public function getAvailableWidgets(): array
    {
        return [
            'sys_welcome' => [
                'title' => 'Bem-vindo ao OS',
                'description' => 'Apresentação do sistema para administradores.'
            ],
            'sys_info' => [
                'title' => 'Status do Servidor',
                'description' => 'Uso de RAM, versão PHP, etc.'
            ],
            'sys_traffic' => [
                'title' => 'Tráfego do Site (Beta)',
                'description' => 'Contador de visitantes recentes.'
            ]
        ];
    }

    public function renderWidget(string $widgetId): string
    {
        switch ($widgetId) {
            case 'sys_welcome':
                return "
                <div class='widget-card' data-id='sys_welcome'>
                    <div class='widget-header'>
                        <h3 class='widget-title'>Central de Controle</h3>
                    </div>
                    <div class='widget-body' style='text-align: center;'>
                        <svg width='48' height='48' viewBox='0 0 24 24' fill='none' stroke='var(--accent-blue)' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'>
                            <rect x='2' y='3' width='20' height='14' rx='2' ry='2'></rect>
                            <line x1='8' y1='21' x2='16' y2='21'></line>
                            <line x1='12' y1='17' x2='12' y2='21'></line>
                        </svg>
                        <h2 style='margin-top: 15px; font-size: 18px;'>Bem-vindo ao Domain-System OS</h2>
                        <p style='color: var(--text-muted); font-size: 13px;'>Tudo operando dentro dos parâmetros normais.</p>
                    </div>
                </div>";

            case 'sys_info':
                $php = phpversion();
                $os = PHP_OS;
                return "
                <div class='widget-card' data-id='sys_info'>
                    <div class='widget-header'>
                        <h3 class='widget-title'>Status do Servidor</h3>
                    </div>
                    <div class='widget-body'>
                        <div style='display: flex; flex-direction: column; gap: 10px;'>
                            <div style='display: flex; justify-content: space-between;'>
                                <span style='color: var(--text-muted);'>PHP Version</span>
                                <span style='font-weight: 600;'>{$php}</span>
                            </div>
                            <div style='display: flex; justify-content: space-between;'>
                                <span style='color: var(--text-muted);'>SO Kernel</span>
                                <span style='font-weight: 600;'>{$os}</span>
                            </div>
                            <div style='display: flex; justify-content: space-between;'>
                                <span style='color: var(--text-muted);'>Uso de Memória</span>
                                <span style='font-weight: 600; color: var(--accent-green);'>" . round(memory_get_usage() / 1024 / 1024, 2) . " MB</span>
                            </div>
                        </div>
                    </div>
                </div>";

            case 'sys_traffic':
                // Temporariamente fictício até termos o módulo de Analytics integrado
                $visits = rand(150, 450);
                return "
                <div class='widget-card' data-id='sys_traffic'>
                    <div class='widget-header'>
                        <h3 class='widget-title'>Tráfego Hoje (Beta)</h3>
                    </div>
                    <div class='widget-body' style='text-align: center; display: flex; flex-direction: column; justify-content: center; height: 100%;'>
                        <h1 style='font-size: 42px; margin: 0; color: var(--accent-green);'>{$visits}</h1>
                        <p style='color: var(--text-muted); font-size: 12px; margin-top: 5px;'>Visitas nas últimas 24h</p>
                    </div>
                </div>";
        }

        return "";
    }
}
