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
            $html = '<div id="clock-widget-container" style="background: linear-gradient(135deg, #1e293b, #0f172a); color: #10b981; padding: 15px; border-radius: 12px; text-align: center; font-family: monospace; box-shadow: inset 0 2px 4px rgba(0,0,0,0.5);">';
            
            // Combobox (Select) for Timezones
            $html .= '<div style="margin-bottom: 15px;">';
            $html .= '<select id="clock-timezone-select" style="background: #0f172a; color: #10b981; border: 1px solid #334155; padding: 5px; border-radius: 4px; font-family: inherit; font-size: 12px; outline: none; cursor: pointer;">';
            $html .= '<option value="system">Relógio Local (Sistema)</option>';
            $html .= '<option value="America/Sao_Paulo">Brasil (Brasília)</option>';
            $html .= '<option value="America/New_York">EUA (Nova Iorque)</option>';
            $html .= '<option value="Europe/London">Reino Unido (Londres)</option>';
            $html .= '<option value="Asia/Tokyo">Japão (Tóquio)</option>';
            $html .= '</select>';
            $html .= '</div>';

            $html .= '<div style="font-size: 28px;">';
            $html .= '<i class="far fa-clock" style="color: #4ade80; margin-right: 10px;"></i>';
            $html .= '<span id="live-clock-text">--:--:--</span>';
            $html .= '</div>';
            
            $html .= '<div style="font-size: 12px; color: #64748b; margin-top: 10px;">Protegido por ACL (clock.view)</div>';
            $html .= '</div>';
            
            // The magic to make it ALIVE (Client-side JS)
            $html .= '<script>
                (function() {
                    let el = document.getElementById("live-clock-text");
                    let select = document.getElementById("clock-timezone-select");
                    if(el && select) {
                        setInterval(() => {
                            let now = new Date();
                            let tz = select.value;
                            
                            let options = { hour12: false };
                            if (tz !== "system") {
                                options.timeZone = tz;
                            }
                            
                            try {
                                el.innerHTML = now.toLocaleTimeString("pt-BR", options);
                            } catch (e) {
                                el.innerHTML = "Erro de Fuso";
                            }
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
