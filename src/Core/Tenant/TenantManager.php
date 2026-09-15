<?php

namespace DomainSystem\Core\Tenant;

use DomainSystem\Core\Http\Request;

class TenantManager
{
    private TenantContext $context;
    
    public function __construct(TenantContext $context)
    {
        $this->context = $context;
    }
    
    /**
     * Tenta descobrir qual é o Tenant acessado baseado na URL atual.
     * Na Fase 1 (Dev), checa a query string ?tenant=. Depois cai pro subdomínio.
     */
    public function resolveFromRequest(Request $request): void
    {
        $host = $request->server['HTTP_HOST'] ?? 'localhost';
        $queryTenant = $request->get('tenant');
        
        if (!empty($queryTenant)) {
            // Dev Mode: Override by query param
            $this->context->setTenantId($queryTenant);
            $this->context->setTenantName(ucfirst($queryTenant) . ' Clínica'); // Nome provisório genérico
            $this->context->setDomain($host);
        } else {
            // Lê o subdomínio ou domínio
            // Ex: clinica_a.localhost -> clinica_a
            $parts = explode('.', $host);
            if (count($parts) > 1 && $parts[0] !== 'www' && $parts[0] !== 'localhost') {
                $subdomain = $parts[0];
                $this->context->setTenantId($subdomain);
                $this->context->setTenantName(ucfirst($subdomain) . ' Clínica');
            } else {
                // Caso fallback (sem tenant explícito na vitrine, talvez seja o Cockpit Admin global)
                // Ou podemos definir um default para testes
                $this->context->setTenantId('master');
                $this->context->setTenantName('Master SaaS Admin');
            }
            $this->context->setDomain($host);
        }
    }
    
    public function getContext(): TenantContext
    {
        return $this->context;
    }
}
