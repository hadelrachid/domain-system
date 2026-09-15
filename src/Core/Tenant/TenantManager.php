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
            $tenantId = $queryTenant;
        } else {
            // Lê o subdomínio ou domínio
            // Ex: clinica_a.localhost -> clinica_a
            $parts = explode('.', $host);
            if (count($parts) > 1 && $parts[0] !== 'www' && $parts[0] !== 'localhost') {
                $tenantId = $parts[0];
            } else {
                $tenantId = 'master';
            }
        }
        
        // Lê o arquivo de configuração mestre de inquilinos
        $tenantsFile = dirname(__DIR__, 3) . '/config/tenants.json';
        $tenants = [];
        if (file_exists($tenantsFile)) {
            $tenants = json_decode(file_get_contents($tenantsFile), true) ?: [];
        }
        
        // Se o tenant existir no json, usa os dados reais, senão faz um fallback seguro
        if (isset($tenants[$tenantId])) {
            $data = $tenants[$tenantId];
            $this->context->setTenantId($data['id']);
            $this->context->setTenantName($data['name']);
            if (isset($data['db'])) {
                $this->context->setDbConfig($data['db']);
            }
        } else {
            // Fallback genérico para Tenants não cadastrados (pode apontar para um DB de erro no futuro)
            $this->context->setTenantId($tenantId);
            $this->context->setTenantName(ucfirst($tenantId) . ' Clínica');
            $this->context->setDbConfig([
                'dsn' => 'mysql:host=localhost;dbname=daher_db;charset=utf8mb4',
                'user' => 'root',
                'pass' => ''
            ]);
        }
        
        $this->context->setDomain($host);
    }
    
    public function getContext(): TenantContext
    {
        return $this->context;
    }
}
