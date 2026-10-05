<?php

namespace DomainSystem\Core\Security;

use DomainSystem\Core\Contracts\ContainerInterface;
use PDO;

/**
 * Motor de Identidade e Privilégios (ACL) do Sistema Operacional Web
 */
class IdentityManager
{
    private ContainerInterface $container;
    private ?PDO $db = null;
    
    /**
     * Cache de memória para evitar múltiplas consultas ao banco na mesma requisição HTTP
     */
    private array $userCapabilitiesCache = [];
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    /**
     * Resolve a conexão com o banco de forma preguiçosa (Lazy Loading)
     * Isso impede que o IdentityManager quebre o boot se o banco ainda não estiver pronto.
     */
    private function getDb(): PDO
    {
        if ($this->db === null) {
            $this->db = $this->container->make(\DomainSystem\SystemApps\Database\Connection::class)->getPdo();
        }
        return $this->db;
    }
    
    /**
     * Verifica se o usuário logado possui a capacidade/privilégio solicitado.
     * Retorna true se possuir a capacidade ou se for um super admin (wildcard '*').
     */
    public function userCan(int $userId, string $capabilitySlug): bool
    {
        if (!isset($this->userCapabilitiesCache[$userId])) {
            $this->loadUserCapabilities($userId);
        }
        
        $caps = $this->userCapabilitiesCache[$userId];
        
        // O privilégio curinga '*' concede acesso a tudo (Equivalente ao root no Linux)
        return in_array('*', $caps) || in_array($capabilitySlug, $caps);
    }
    
    /**
     * Verifica se o usuário pertence a um grupo/cargo específico (Retrocompatibilidade)
     */
    public function hasRole(int $userId, string $roleSlug): bool
    {
        $stmt = $this->getDb()->prepare("
            SELECT 1 FROM user_roles ur 
            JOIN roles r ON ur.role_id = r.id 
            WHERE ur.user_id = :user_id AND r.slug = :role
        ");
        $stmt->execute([':user_id' => $userId, ':role' => $roleSlug]);
        return (bool) $stmt->fetch();
    }
    
    /**
     * Carrega todas as capacidades do usuário do banco de dados para a memória
     */
    private function loadUserCapabilities(int $userId): void
    {
        $db = $this->getDb();
        
        $sql = "
            SELECT DISTINCT c.slug 
            FROM capabilities c
            JOIN role_capabilities rc ON c.id = rc.capability_id
            JOIN roles r ON rc.role_id = r.id
            JOIN user_roles ur ON r.id = ur.role_id
            WHERE ur.user_id = :user_id
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        $capabilities = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Se o usuário pertencer ao grupo "admin", ele recebe o curinga total (Bypass)
        if ($this->hasRole($userId, 'admin')) {
            $capabilities[] = '*';
        }
        
        $this->userCapabilitiesCache[$userId] = $capabilities;
    }
}
