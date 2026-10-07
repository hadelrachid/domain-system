<?php
namespace DomainSystem\Plugins\clock_widget;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Contracts\OsConnectorInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    public function register(): void {}

    // Fase de Negociação
    public function osRegister(OsConnectorInterface $os): void
    {
        // O Plugin pede permissão para injetar Widgets no Dashboard Admin
        $os->listenHook('admin.dashboard.widgets');
        
        // Exige o IdentityManager para testar se o usuário tem a permissão
        $os->requireLink('core.identity');
    }

    // Fase de Execução
    public function osBoot(OsRuntimeInterface $runtime): void
    {
        $identity = $runtime->getLink('core.identity');

        $runtime->onHook('admin.dashboard.widgets', function($widgets) use ($identity) {
            // Verifica se o usuário autenticado atual possui a Capability
            // O ID do usuário na sessão está no $_SESSION['auth_user_id']
            $userId = $_SESSION['auth_user_id'] ?? 0;
            
            // Se tiver a permissão (ou for super admin '*') ele vê o widget!
            if ($identity->userCan($userId, 'clock.view')) {
                $html = '<div style="background: linear-gradient(135deg, #1e293b, #0f172a); color: #10b981; padding: 25px; border-radius: 12px; text-align: center; font-family: monospace; font-size: 28px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.5);">';
                $html .= '<i class="far fa-clock" style="color: #4ade80; margin-right: 10px;"></i>';
                $html .= date('H:i:s');
                $html .= '<div style="font-size: 12px; color: #64748b; margin-top: 5px;">Relógio Mundial Seguro (via ACL)</div>';
                $html .= '</div>';
                
                $widgets[] = [
                    'id' => 'clock_widget',
                    'title' => 'Relógio (Requer Permissão: clock.view)',
                    'content' => $html,
                    'width' => 'half' // Usa meia tela do dashboard
                ];
            } else {
                $html = '<div style="background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 8px; font-size: 14px; text-align: center; border: 1px solid #f87171;">';
                $html .= '<i class="fas fa-lock"></i> Você não tem o Privilégio (Capability) <strong>clock.view</strong> para ver as horas.';
                $html .= '</div>';
                
                $widgets[] = [
                    'id' => 'clock_widget_locked',
                    'title' => 'Relógio (Acesso Negado)',
                    'content' => $html,
                    'width' => 'half'
                ];
            }

            return $widgets;
        });
    }

    // Ocorre APENAS quando o SysAdmin clica em "Ativar/Instalar"
    public function activate(OsRuntimeInterface $runtime): void
    {
        // Aqui o Plugin injeta silenciosamente as Capabilities no SO!
        $runtime->registerCapability('clock.view', 'App Relógio Mundial');
        $runtime->registerCapability('clock.configure', 'App Relógio Mundial');
    }
}
