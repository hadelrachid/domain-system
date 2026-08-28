<?php

namespace DomainSystem\Plugins\health_insurances;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Events\EventDispatcher;
use DomainSystem\Plugins\health_insurances\Services\BillingService;
use DomainSystem\Plugins\health_insurances\Providers\UnimedProvider;
use DomainSystem\Plugins\health_insurances\Providers\PrivateProvider;

class Plugin extends AbstractPlugin
{
    public function register(): void
    {
        /** @var EventDispatcher $events */
        $events = $this->container->make(EventDispatcher::class);

        // Bind do Serviço de Faturamento (O Filtro de Linha)
        $this->container->bind(BillingService::class, function() {
            // Em um cenário real mais robusto, poderíamos usar o container para resolver isso,
            // mas aqui vamos passar a fábrica diretamente ou instanciar de forma limpa.
            return new BillingService();
        });

        // Registrar o shortcode de prova de conceito
        $events->addListener('init', function() {
            if (function_exists('add_shortcode')) {
                add_shortcode('teste_faturamento', function($attr) {
                    $convenioStr = $attr['convenio'] ?? 'particular';
                    $paciente = $attr['paciente'] ?? 'João da Silva';
                    
                    /** @var BillingService $billing */
                    $billing = \DomainSystem\Core\Application::getInstance()->getContainer()->make(BillingService::class);
                    
                    try {
                        $resultado = $billing->processConsultation($convenioStr, $paciente);
                        
                        $cor = $resultado['authorized'] ? '#22c55e' : '#ef4444';
                        
                        return "<div style='border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px; background: #fff;'>
                                    <h4 style='margin:0 0 10px 0;'>Simulação de Faturamento via Interface</h4>
                                    <div><strong>Convênio:</strong> {$resultado['provider']}</div>
                                    <div><strong>Autorizado:</strong> <span style='color: {$cor}; font-weight: bold;'>" . ($resultado['authorized'] ? 'SIM' : 'NÃO') . "</span></div>
                                    <div style='margin-top: 10px; font-size: 18px; font-weight: bold;'>Valor a faturar: R$ " . number_format($resultado['price'], 2, ',', '.') . "</div>
                                </div>";
                    } catch (\Exception $e) {
                        return "<div style='color: red; padding: 10px;'>Erro: " . $e->getMessage() . "</div>";
                    }
                }, 'Testa o Polimorfismo e as Interfaces dos Convênios', ['convenio' => 'Nome do convênio (ex: unimed, particular)']);
            }
        });
    }
}
