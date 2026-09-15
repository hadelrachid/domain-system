<?php

namespace DomainSystem\Core\Tenant;

class TenantContext
{
    private ?string $tenantId = null;
    private ?string $tenantName = null;
    private ?string $domain = null;
    private array $dbConfig = [];
    
    /**
     * Define as credenciais do banco de dados deste Tenant
     */
    public function setDbConfig(array $config): void
    {
        $this->dbConfig = $config;
    }
    
    /**
     * Retorna as credenciais do banco
     */
    public function getDbConfig(): array
    {
        return $this->dbConfig;
    }
    
    /**
     * Define the active tenant ID
     */
    public function setTenantId(string $tenantId): void
    {
        $this->tenantId = $tenantId;
    }
    
    /**
     * Get the active tenant ID
     */
    public function getTenantId(): ?string
    {
        return $this->tenantId;
    }
    
    /**
     * Define the active tenant Name
     */
    public function setTenantName(string $tenantName): void
    {
        $this->tenantName = $tenantName;
    }
    
    /**
     * Get the active tenant Name
     */
    public function getTenantName(): ?string
    {
        return $this->tenantName;
    }
    
    /**
     * Define the active domain
     */
    public function setDomain(string $domain): void
    {
        $this->domain = $domain;
    }
    
    /**
     * Get the active domain
     */
    public function getDomain(): ?string
    {
        return $this->domain;
    }
    
    /**
     * Check if a tenant is currently loaded
     */
    public function isLoaded(): bool
    {
        return $this->tenantId !== null;
    }
}
