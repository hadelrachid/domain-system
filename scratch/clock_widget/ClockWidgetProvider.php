<?php
namespace DomainSystem\Plugins\clock_widget;

use DomainSystem\Core\Contracts\DashboardWidgetProviderInterface;
use DomainSystem\Core\Security\IdentityManager;

class ClockWidgetProvider implements DashboardWidgetProviderInterface
{
    private IdentityManager $identity;

    public function __construct(IdentityManager $identity)
    {
        $this->identity = $identity;
    }

    public function getProviderName(): string
    {
        return 'App Relógio Mundial';
    }

    public function getAvailableWidgets(): array
    {
        return [
            'world_clock' => [
                'title' => 'Relógio Mundial Seguro',
                'description' => 'Exibe o horário mundial protegido por ACL'
            ]
        ];
    }

    public function renderWidget(string $widgetId): string
    {
        if ($widgetId !== 'world_clock') {
            return '';
        }

        $userId = $_SESSION['user_id'] ?? 0;
        
        if ($this->identity->userCan($userId, 'clock.view')) {
            $time = date('H:i:s');
            $html = '<div id="clock-widget-container" style="background: linear-gradient(135deg, #1e293b, #0f172a); color: #10b981; padding: 25px; border-radius: 12px; text-align: center; font-family: monospace; font-size: 28px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.5);">';
            $html .= '<i class="far fa-clock" style="color: #4ade80; margin-right: 10px;"></i>';
            $html .= '<span id="live-clock-text">' . $time . '</span>';
            $html .= '<div style="font-size: 12px; color: #64748b; margin-top: 5px;">Relógio Mundial Seguro (via ACL)</div>';
            $html .= '</div>';
            
            // The magic to make it ALIVE (Client-side JS)
            $html .= '<script>
                (function() {
                    let el = document.getElementById("live-clock-text");
                    if(el) {
                        setInterval(() => {
                            let now = new Date();
                            el.innerHTML = now.toLocaleTimeString("pt-BR", {hour12: false});
                        }, 1000);
                    }
                })();
            </script>';
            
            return $html;
        }

        $html = '<div style="background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 8px; font-size: 14px; text-align: center; border: 1px solid #f87171;">';
        $html .= '<i class="fas fa-lock"></i> Você não tem o Privilégio (Capability) <strong>clock.view</strong> para ver as horas.';
        $html .= '</div>';
        return $html;
    }
}
