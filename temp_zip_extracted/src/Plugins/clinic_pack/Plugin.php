<?php

namespace DomainSystem\Plugins\clinic_pack;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Events\EventDispatcher;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Contracts\CockpitRegistryInterface;
use DomainSystem\Plugins\clinic_pack\Controllers\AdminDashboardController;
use DomainSystem\Plugins\clinic_pack\Controllers\DoctorDashboardController;
use DomainSystem\Plugins\clinic_pack\Controllers\SecretaryDashboardController;
use DomainSystem\Plugins\clinic_pack\Controllers\NursingDashboardController;
use DomainSystem\Plugins\clinic_pack\Controllers\CockpitAjaxController;
use DomainSystem\Plugins\clinic_pack\Controllers\ProfileController;
use DomainSystem\Plugins\clinic_pack\Controllers\SettingsController;
use DomainSystem\Plugins\clinic_pack\Providers\DoctorCockpitProvider;
use DomainSystem\Plugins\clinic_pack\Providers\SecretaryCockpitProvider;
use DomainSystem\Plugins\clinic_pack\Providers\NursingCockpitProvider;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Contracts\OsConnectorInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    // Ignorado pelo OS 2.0
    public function register(): void {}

    // ==========================================
    // 1. FASE DE NEGOCIAÇÃO (OS 2.0)
    // ==========================================
    public function osRegister(OsConnectorInterface $os): void
    {
        // O clinic_pack (como Hub) avisa ao OS quais canais ele vai escutar
        $os->listenHook('router.register');
        $os->listenHook('shortcodes.register');
        $os->listenHook('admin.menu');
        $os->listenHook('admin.plugins.list');
    }

    // ==========================================
    // 2. FASE DE EXECUÇÃO (OS 2.0)
    // ==========================================
    public function osBoot(OsRuntimeInterface $runtime): void
    {
        // Registra o provedor de Widgets no Registry Global
        try {
            $registry = \DomainSystem\Core\Application::getInstance()->getContainer()->make(\DomainSystem\Core\Registry\DashboardWidgetRegistry::class);
            $registry->registerProvider(new \DomainSystem\Plugins\clinic_pack\Widgets\ClinicDashboardWidgetProvider());
        } catch (\Exception $e) {}

        // Registrando dependências locais do pacote
        $this->container->bind(
            \DomainSystem\Plugins\clinic_pack\Contracts\UserProfileServiceInterface::class,
            \DomainSystem\Plugins\clinic_pack\Services\UserProfileService::class
        );

        // 1. Registra os Cockpits usando o Container
        if ($this->container->has(CockpitRegistryInterface::class)) {
            $registry = $this->container->make(CockpitRegistryInterface::class);
            $registry->registerProvider($this->container->make(DoctorCockpitProvider::class));
            $registry->registerProvider($this->container->make(SecretaryCockpitProvider::class));
            $registry->registerProvider($this->container->make(NursingCockpitProvider::class));
        }

        // 2. Roteamento (Através dos Hooks do OS 2.0)
        $runtime->onHook('router.register', function(Router $router) {
            $router->addRoute('GET', '/admin/clinic/settings', [SettingsController::class, 'index'], 'clinic_admin', ['admin']);
            $router->addRoute('POST', '/admin/clinic/settings/save', [SettingsController::class, 'save'], 'clinic_admin', ['admin']);
            $router->addRoute('POST', '/admin/clinic/settings/insurance/add', [SettingsController::class, 'addInsurance'], 'clinic_admin', ['admin']);
            $router->addRoute('POST', '/admin/clinic/settings/insurance/delete', [SettingsController::class, 'deleteInsurance'], 'clinic_admin', ['admin']);
            
            $router->addRoute('GET', '/cockpit/doctor', [DoctorDashboardController::class, 'index'], 'cockpit', ['doctor', 'admin']);
            $router->addRoute('GET', '/cockpit/secretary', [SecretaryDashboardController::class, 'index'], 'cockpit', ['secretary', 'receptionist', 'admin']);
            $router->addRoute('GET', '/cockpit/nursing', [NursingDashboardController::class, 'index'], 'cockpit', ['nurse', 'admin']);
            
            $router->addRoute('POST', '/cockpit/search', [CockpitAjaxController::class, 'searchHistory'], 'cockpit', ['doctor', 'secretary', 'receptionist', 'admin']);
            $router->addRoute('POST', '/cockpit/history-tab', [CockpitAjaxController::class, 'renderHistoryTabAjax'], 'cockpit', ['doctor', 'secretary', 'receptionist', 'admin']);
            $router->addRoute('POST', '/cockpit/tab-ajax', [CockpitAjaxController::class, 'renderTabAjax'], 'cockpit', ['doctor', 'secretary', 'receptionist', 'admin']);
            $router->addRoute('POST', '/cockpit/sync', [CockpitAjaxController::class, 'syncPanels'], 'cockpit', ['doctor', 'secretary', 'receptionist', 'admin']);
            $router->addRoute('POST', '/cockpit/status', [CockpitAjaxController::class, 'updateAppointmentStatus'], 'cockpit', ['doctor', 'secretary', 'receptionist', 'admin']);
            
            $router->addRoute('POST', '/cockpit/profile', [ProfileController::class, 'updateProfile'], 'cockpit', ['admin', 'doctor', 'secretary', 'receptionist', 'nurse', 'patient']);
            $router->addRoute('GET', '/cockpit/profile/2fa', [ProfileController::class, 'generate2fa'], 'cockpit', ['admin', 'doctor', 'secretary', 'receptionist', 'nurse', 'patient']);
            $router->addRoute('POST', '/cockpit/profile/2fa', [ProfileController::class, 'confirm2fa'], 'cockpit', ['admin', 'doctor', 'secretary', 'receptionist', 'nurse', 'patient']);
            
            $router->addRoute('GET', '/admin/clinic', [AdminDashboardController::class, 'index'], 'clinic_admin', ['admin']);
            $router->addRoute('GET', '/admin/clinic/shortcodes', [AdminDashboardController::class, 'shortcodesCatalog'], 'clinic_admin', ['admin']);
        });

        // 2.5 Registro de Shortcodes
        $runtime->onHook('shortcodes.register', function($manager) {
            $manager->add('aba_aguardando', [\DomainSystem\Plugins\clinic_pack\Theme\CockpitShortcodes::class, 'renderAbaAguardando'], 'Aba: Pacientes Aguardando', [], 'Agenda/Recepção');
            $manager->add('aba_confirmados_hoje', [\DomainSystem\Plugins\clinic_pack\Theme\CockpitShortcodes::class, 'renderAbaConfirmadosHoje'], 'Aba: Confirmados Hoje', ['role' => 'secretary ou doctor'], 'Agenda/Recepção');
            $manager->add('aba_nao_compareceu', [\DomainSystem\Plugins\clinic_pack\Theme\CockpitShortcodes::class, 'renderAbaNaoCompareceu'], 'Aba: Pacientes que Faltaram', ['role' => 'secretary'], 'Agenda/Recepção');
            $manager->add('aba_historico', [\DomainSystem\Plugins\clinic_pack\Theme\CockpitShortcodes::class, 'renderAbaHistorico'], 'Aba: Histórico', ['role' => 'secretary ou doctor', 'type' => 'all ou today'], 'Histórico');
            $manager->add('aba_pesquisar', [\DomainSystem\Plugins\clinic_pack\Theme\CockpitShortcodes::class, 'renderAbaPesquisar'], 'Aba: Pesquisar', [], 'Histórico');
            
            // Perfil
            $manager->add('modal_perfil', [\DomainSystem\Plugins\clinic_pack\Theme\ProfileShortcodes::class, 'renderModalPerfil'], 'Container do Modal de Perfil', [], 'Interface/Perfil');
            $manager->add('form_dados_pessoais', [\DomainSystem\Plugins\clinic_pack\Theme\ProfileShortcodes::class, 'renderFormDadosPessoais'], 'Formulário de dados do usuário (Foto, E-mail, Senha)', [], 'Formulários');
            $manager->add('form_autenticacao_2fa', [\DomainSystem\Plugins\clinic_pack\Theme\ProfileShortcodes::class, 'renderFormAutenticacao2FA'], 'Opções de Autenticação em 2 Fatores', [], 'Formulários');
            $manager->add('botao_logout', [\DomainSystem\Plugins\clinic_pack\Theme\ProfileShortcodes::class, 'renderBotaoLogout'], 'Botão de Sair do Sistema', [], 'Interface/Perfil');
            $manager->add('relogio_digital', [\DomainSystem\Plugins\clinic_pack\Theme\ProfileShortcodes::class, 'renderRelogioDigital'], 'Relógio digital ao vivo com data de hoje', [], 'Interface/Perfil');
            $manager->add('widget_perfil_header', [\DomainSystem\Plugins\clinic_pack\Theme\ProfileShortcodes::class, 'renderWidgetPerfilHeader'], 'Botão de perfil no cabeçalho com engrenagem', [], 'Interface/Perfil');
        });

        // 3. Modificando o Menu (Para colocar tudo dentro de Daher Clínica)
        $runtime->onHook('admin.menu', function(array $menu) {
            $clinicSubmenus = [];
            $clinicUrls = [
                '/admin/patients', 
                '/admin/appointments', 
                '/admin/appointments/history', 
                '/admin/doctors', 
                '/admin/medical_records', 
                '/admin/triage', 
                '/admin/certificates', 
                '/admin/finance', 
                'admin/whatsapp',
                '/admin/whatsapp'
            ];
            
            foreach ($menu as $k => $item) {
                if (in_array($item['url'] ?? '', $clinicUrls)) {
                    $clinicSubmenus[] = $item;
                    unset($menu[$k]);
                }
            }
            if (!empty($clinicSubmenus)) {
                $clinicSubmenus[] = [
                    'title' => 'Catálogo de Shortcodes',
                    'url' => '/admin/clinic/shortcodes',
                    'icon' => '🧩'
                ];
                
                $clinicSubmenus[] = [
                    'title' => 'Configurações da Clínica',
                    'url' => '/admin/clinic/settings',
                    'icon' => '⚙️'
                ];
                
                $menu[] = [
                    'title' => 'Gestão Clínica',
                    'icon' => '🏥',
                    'submenu' => $clinicSubmenus,
                    'url' => '/admin/clinic'
                ];
            }
            return array_values($menu);
        }); // Removida a prioridade 999 por enquanto, pois o OS Dispatcher gerencia

        // 4. Ocultar micro-plugins da lista principal
        $runtime->onHook('admin.plugins.list', function(array $plugins) {
            $hidden = ['patients', 'doctors', 'appointments', 'triage', 'medical_records', 'whatsapp', 'finance'];
            $bundled = [];
            foreach ($plugins as $k => $p) {
                if (in_array($p['folder'], $hidden)) {
                    $bundled[] = $p;
                    unset($plugins[$k]);
                }
            }
            
            foreach ($plugins as $k => $p) {
                if ($p['folder'] === 'clinic_pack') {
                    if (!empty($bundled)) {
                        $existing = $p['subplugins'] ?? [];
                        $plugins[$k]['subplugins'] = array_merge($existing, $bundled);
                    }
                    break;
                }
            }
            return array_values($plugins);
        });
    }

    // Mantemos a interface oficial do Hub! O Bootstrapper chama isso para ler as subpastas.
    public function getSubPluginsPath(): ?string
    {
        return __DIR__ . '/bundled_plugins';
    }
}


