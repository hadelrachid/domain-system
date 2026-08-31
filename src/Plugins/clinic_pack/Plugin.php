<?php

namespace DomainSystem\Plugins\clinic_pack;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Events\EventDispatcher;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Contracts\CockpitRegistryInterface;
use DomainSystem\Plugins\clinic_pack\Controllers\CockpitController;
use DomainSystem\Plugins\clinic_pack\Controllers\SettingsController;
use DomainSystem\Plugins\clinic_pack\Providers\DoctorCockpitProvider;
use DomainSystem\Plugins\clinic_pack\Providers\SecretaryCockpitProvider;
use DomainSystem\Plugins\clinic_pack\Providers\NursingCockpitProvider;

class Plugin extends AbstractPlugin
{
    public function register(): void
    {
        /** @var EventDispatcher $events */
        $events = $this->events();

        // 1. Registra os Cockpits
        if ($this->container->has(CockpitRegistryInterface::class)) {
            /** @var CockpitRegistryInterface $registry */
            $registry = $this->container->make(CockpitRegistryInterface::class);
            $registry->registerProvider(new DoctorCockpitProvider());
            $registry->registerProvider(new SecretaryCockpitProvider());
            $registry->registerProvider(new NursingCockpitProvider());
        }

        // 2. Roteamento do Cockpit e Admin Dashboard
        $events->addListener('router.register', function(Router $router) {
            $router->addRoute('GET', '/admin/clinic/settings', [SettingsController::class, 'index'], 'clinic_admin', ['admin']);
            $router->addRoute('POST', '/admin/clinic/settings/save', [SettingsController::class, 'save'], 'clinic_admin', ['admin']);
            $router->addRoute('GET', '/cockpit/doctor', [CockpitController::class, 'renderDoctor'], 'cockpit', ['doctor', 'admin']);
            $router->addRoute('GET', '/cockpit/secretary', [CockpitController::class, 'renderSecretary'], 'cockpit', ['receptionist', 'admin']);
            $router->addRoute('GET', '/cockpit/nursing', [CockpitController::class, 'renderNursing'], 'cockpit', ['nurse', 'admin']);
            $router->addRoute('GET', '/admin/clinic', [CockpitController::class, 'renderAdminDashboard'], 'clinic_admin', ['admin']);
            $router->addRoute('GET', '/admin/clinic/shortcodes', [CockpitController::class, 'renderShortcodesCatalog'], 'clinic_admin', ['admin']);
        });

        // 3. Modificando o Menu (Para colocar tudo dentro de Daher Clínica)
        $events->addListener('admin.menu', function(array $menu) {
            $clinicSubmenus = [];
            foreach ($menu as $k => $item) {
                if (in_array($item['title'] ?? '', ['Pacientes', 'Agendamentos', 'Médicos', 'Prontuários', 'Histórico', 'Triagem', 'Atestados', 'Financeiro', 'WhatsApp Z-API'])) {
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
                
                // Adiciona Configurações ao grupo da clínica
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
        }, 999);

        // 4. Ocultar micro-plugins da lista principal (Régua de tomadas)
        $events->addListener('admin.plugins.list', function(array $plugins) {
            $hidden = ['patients', 'doctors', 'appointments', 'triage', 'medical_records', 'whatsapp', 'finance'];
            $bundled = [];
            foreach ($plugins as $k => $p) {
                if (in_array($p['folder'], $hidden)) {
                    $bundled[] = $p;
                    unset($plugins[$k]);
                }
            }
            
            // Injeta como subplugins no clinic_pack
            foreach ($plugins as $k => $p) {
                if ($p['folder'] === 'clinic_pack') {
                    $plugins[$k]['subplugins'] = $bundled;
                    break;
                }
            }
            return array_values($plugins);
        });
    }

    public function getSubPluginsPath(): ?string
    {
        return __DIR__ . '/bundled_plugins';
    }
}
