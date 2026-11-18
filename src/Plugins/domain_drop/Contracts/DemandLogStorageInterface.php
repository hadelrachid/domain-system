<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Contracts;

use DomainSystem\Plugins\domain_drop\DTO\DemandEvent;

/**
 * Contrato de armazenamento de logs de demanda em arquivos JSON mensais (SRP).
 *
 * Evita poluição do banco de dados relacional com milhares de registros de cliques brutos.
 * Organiza os arquivos em pastas estruturadas:
 *   storage/demand_logs/{ano}/json_{nome_do_mes}_{ano}/interactions.json
 */
interface DemandLogStorageInterface
{
    /**
     * Registra o evento de interação no arquivo JSON mensal correspondente.
     */
    public function appendInteraction(DemandEvent $event): void;

    /**
     * Retorna os eventos recentes do período (ex: '2026-11' ou null para mês atual),
     * em ordem cronológica decrescente (mais recentes primeiro).
     *
     * @param string|null $yearMonth Formato 'YYYY-MM' ou null para mês corrente
     * @param int $limit Máximo de registros a retornar
     * @return array<int, array<string, mixed>>
     */
    public function getRecentInteractions(?string $yearMonth = null, int $limit = 50): array;

    /**
     * Retorna todos os e-mails únicos de clientes capturados no período.
     *
     * @param string|null $yearMonth Formato 'YYYY-MM' ou null para mês corrente
     * @return array<int, string>
     */
    public function getLeadEmails(?string $yearMonth = null): array;

    /**
     * Expurga pastas de anos que excedem a política de retenção (pastas somem a cada 2 anos).
     *
     * @param int $retentionYears Anos de retenção (padrão: 2)
     * @return int Quantidade de diretórios removidos
     */
    public function purgeExpiredLogs(int $retentionYears = 2): int;

    /**
     * Retorna o caminho absoluto do arquivo JSON para o período informado.
     */
    public function getFilePathForPeriod(?string $yearMonth = null): string;

    /**
     * Promove interações anônimas de uma sessão e/ou endereço de IP para um cliente identificado.
     * Atualiza registros que estavam como 'externo' para 'cliente_logado'.
     *
     * @param string $sessionId Identificador da sessão PHP a promover
     * @param int $customerId ID do cliente cadastrado
     * @param string $customerName Nome completo do cliente
     * @param string $customerEmail E-mail do cliente
     * @param string|null $ipAddress Endereço IP do cliente para correlação por rede/dispositivo
     * @param string|null $yearMonth Período YYYY-MM ou null para mês corrente
     * @return int Quantidade de registros promovidos
     */
    public function promoteInteractions(
        string $sessionId,
        int $customerId,
        string $customerName,
        string $customerEmail,
        ?string $ipAddress = null,
        ?string $yearMonth = null
    ): int;

    /**
     * Promove interações anônimas de uma sessão para um cliente identificado.
     * Atualiza registros daquela sessão que estavam como 'externo' para 'cliente_logado'.
     *
     * @param string $sessionId Identificador da sessão PHP a promover
     * @param int $customerId ID do cliente cadastrado
     * @param string $customerName Nome completo do cliente
     * @param string $customerEmail E-mail do cliente
     * @param string|null $yearMonth Período YYYY-MM ou null para mês corrente
     * @return int Quantidade de registros promovidos
     */
    public function promoteSessionInteractions(
        string $sessionId,
        int $customerId,
        string $customerName,
        string $customerEmail,
        ?string $yearMonth = null
    ): int;
}
