<?php

namespace DomainSystem\Plugins\whatsapp;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Plugins\whatsapp\Controllers\WhatsAppSettingsController;

class Plugin extends AbstractPlugin
{
    public function register(): void
    {
        // SOLID: Inversão de Dependências
        $this->container->bind(
            \DomainSystem\Plugins\whatsapp\Contracts\WhatsAppSettingsRepositoryInterface::class,
            \DomainSystem\Plugins\whatsapp\Repositories\SqliteWhatsAppSettingsRepository::class
        );
        $this->container->bind(
            \DomainSystem\Plugins\whatsapp\Contracts\WhatsAppProviderInterface::class,
            \DomainSystem\Plugins\whatsapp\Services\ZApiService::class
        );

        /** @var \DomainSystem\Core\Events\EventDispatcher $events */
        $events = $this->container->make(\DomainSystem\Core\Events\EventDispatcher::class);

        // Add plugin to admin menu
        $events->addListener('admin.menu', function($menus) {
            $menus[] = [
                'title' => 'WhatsApp Z-API',
                'url' => 'admin/whatsapp',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>'
            ];
            return $menus;
        });

        // Register router dynamically when needed
        $events->addListener('router.register', function(Router $router) {
            $router->addRoute('GET', '/admin/whatsapp', [WhatsAppSettingsController::class, 'index'], 'whatsapp', ['admin']);
            $router->addRoute('POST', '/admin/whatsapp/save', [WhatsAppSettingsController::class, 'save'], 'whatsapp', ['admin']);
            $router->addRoute('POST', '/admin/whatsapp/test', [WhatsAppSettingsController::class, 'testMessage'], 'whatsapp', ['admin']);
        });

        // Hook into appointment creation
        $events->addListener('appointment.created', function(array $data) {
            try {
                /** @var \DomainSystem\Plugins\whatsapp\Contracts\WhatsAppProviderInterface $provider */
                $provider = $this->container->make(\DomainSystem\Plugins\whatsapp\Contracts\WhatsAppProviderInterface::class);
                
                /** @var \DomainSystem\Plugins\whatsapp\Contracts\WhatsAppSettingsRepositoryInterface $repository */
                $repository = $this->container->make(\DomainSystem\Plugins\whatsapp\Contracts\WhatsAppSettingsRepositoryInterface::class);
                
                $provider->setConfig($repository->getSettings());
                
                $phone = $data['patient_phone'] ?? '';
                if (!empty($phone)) {
                    $patientName = $data['patient_name'] ?? 'Paciente';
                    $date = $data['appointment_date'] ?? '';
                    $time = $data['appointment_time'] ?? '';
                    
                    // Format date to BR
                    $dateBr = date('d/m/Y', strtotime($date));
                    
                    $msg = "Olá {$patientName},\n\nSua consulta foi agendada com sucesso para o dia *{$dateBr}* às *{$time}*.\n\nEquipe Domain-System.";
                    
                    // Send asynchronously or catch exceptions to not break the UI
                    $provider->sendMessage($phone, $msg);
                }
            } catch (\Exception $e) {
                error_log("Falha ao enviar WhatsApp de confirmacao: " . $e->getMessage());
            }
        });
    }
}
