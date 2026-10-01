<?php

namespace DomainSystem\Plugins\analytics\Widgets;

use DomainSystem\Core\Contracts\DashboardWidgetProviderInterface;
use DomainSystem\Core\Application;

class AnalyticsWidgetProvider implements DashboardWidgetProviderInterface
{
    public function getProviderName(): string
    {
        return "RachidD Analytics (Monitor)";
    }

    public function getAvailableWidgets(): array
    {
        return [
            'anl_pageviews_month' => [
                'title' => 'Visitas no Mês',
                'description' => 'Acessos únicos e totais no mês atual.'
            ],
            'anl_clicks_month' => [
                'title' => 'Cliques de Conversão',
                'description' => 'Acessos a botões de venda e WhatsApp.'
            ]
        ];
    }

    public function renderWidget(string $widgetId): string
    {
        try {
            $db = Application::getInstance()->getContainer()->make(\DomainSystem\Plugins\Database\Connection::class)->getPdo();
            
            $startOfMonth = date('Y-m-01 00:00:00');
            $endOfMonth = date('Y-m-t 23:59:59');

            if ($widgetId === 'anl_pageviews_month') {
                $stmt = $db->prepare("SELECT COUNT(*) as total, COUNT(DISTINCT session_hash) as unique_visits FROM analytics_events WHERE event_type = 'pageview' AND created_at BETWEEN ? AND ?");
                $stmt->execute([$startOfMonth, $endOfMonth]);
                $row = $stmt->fetch();
                $total = $row['total'] ?? 0;
                $unique = $row['unique_visits'] ?? 0;

                return "
                <div class='widget-card' data-id='anl_pageviews_month'>
                    <div class='widget-header'>
                        <h3 class='widget-title'>Tráfego em " . date('M/Y') . "</h3>
                    </div>
                    <div class='widget-body' style='text-align: center; display: flex; flex-direction: column; justify-content: center; height: 100%;'>
                        <h1 style='font-size: 46px; margin: 0; color: var(--accent-blue);'>{$total}</h1>
                        <p style='color: var(--text-muted); font-size: 13px; margin-top: 5px;'>Visualizações Totais</p>
                        
                        <div style='margin-top: 15px; background: rgba(255,255,255,0.05); padding: 8px; border-radius: 6px;'>
                            <span style='font-size: 16px; font-weight: bold; color: var(--accent-green);'>{$unique}</span>
                            <span style='font-size: 12px; color: var(--text-muted);'> Visitantes Únicos</span>
                        </div>
                    </div>
                </div>";
            }

            if ($widgetId === 'anl_clicks_month') {
                $stmt = $db->prepare("SELECT event_source, COUNT(*) as cliq FROM analytics_events WHERE event_type = 'click' AND created_at BETWEEN ? AND ? GROUP BY event_source ORDER BY cliq DESC LIMIT 5");
                $stmt->execute([$startOfMonth, $endOfMonth]);
                $clicks = $stmt->fetchAll();

                $html = "
                <div class='widget-card' data-id='anl_clicks_month'>
                    <div class='widget-header'>
                        <h3 class='widget-title'>Cliques & Conversões</h3>
                    </div>
                    <div class='widget-body'>
                        <ul style='list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 12px;'>";
                
                if (empty($clicks)) {
                    $html .= "<li style='color: var(--text-muted); text-align: center; font-size: 12px; margin-top: 20px;'>Nenhum clique registrado ainda.</li>";
                } else {
                    foreach ($clicks as $c) {
                        $source = htmlspecialchars($c['event_source']);
                        $qtd = $c['cliq'];
                        
                        // Icon definition
                        $iconColor = 'var(--text-muted)';
                        if (str_contains(strtolower($source), 'amazon')) $iconColor = '#FF9900';
                        if (str_contains(strtolower($source), 'hotmart')) $iconColor = '#F04E23';
                        if (str_contains(strtolower($source), 'whatsapp')) $iconColor = '#25D366';

                        $html .= "
                        <li style='display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 8px;'>
                            <span style='display: flex; align-items: center; gap: 8px; font-size: 13px;'>
                                <span style='display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: {$iconColor};'></span>
                                {$source}
                            </span>
                            <span style='font-weight: bold; background: rgba(255,255,255,0.1); padding: 2px 8px; border-radius: 12px; font-size: 11px;'>{$qtd}</span>
                        </li>";
                    }
                }

                $html .= "</ul></div></div>";
                return $html;
            }

        } catch (\Exception $e) {
            return "<div class='widget-card'>Erro no DB: " . $e->getMessage() . "</div>";
        }

        return "";
    }
}
