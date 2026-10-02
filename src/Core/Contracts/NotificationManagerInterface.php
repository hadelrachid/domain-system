<?php

namespace DomainSystem\Core\Contracts;

interface NotificationManagerInterface
{
    /**
     * Registra uma notificação no painel de controle.
     * 
     * @param string $message A mensagem a ser exibida.
     * @param string $type warning, error, info, success
     * @param string|null $source O módulo/plugin que gerou (ex: 'No-Break Shield')
     * @param array $context Metadados adicionais (ex: file, line, trace)
     */
    public function push(string $message, string $type = 'info', ?string $source = null, array $context = []): void;

    /**
     * Retorna notificações não lidas para exibir alertas toast no front.
     */
    public function getUnread(): array;

    /**
     * Marca todas como lidas (após exibir os toasts).
     */
    public function markAsRead(): void;

    /**
     * Retorna o histórico completo de notificações persistidas.
     */
    public function getAll(): array;

    /**
     * Limpa o registro.
     */
    public function clear(): void;
}
