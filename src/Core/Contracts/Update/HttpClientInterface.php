<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts\Update;

use DomainSystem\Core\Update\Exception\TransportException;
use DomainSystem\Core\Update\Value\HttpResponse;

/**
 * Contrato de comunicação HTTP segura para o sistema de atualização.
 *
 * Obrigações da implementação:
 * - Permitir exclusivamente HTTPS em porta padrão.
 * - Rejeitar URLs com credenciais embutidas (userinfo) ou hosts fora da allowlist.
 * - Controlar redirecionamentos manualmente revalidando cada salto contra a allowlist.
 */
interface HttpClientInterface
{
    /**
     * Executa uma requisição GET e devolve a resposta em memória.
     *
     * @param array<string, string> $headers
     * @throws TransportException Em falha de rede, protocolo não-HTTPS ou host fora da allowlist.
     */
    public function get(string $url, array $headers = [], int $timeoutSeconds = 10): HttpResponse;

    /**
     * Baixa um recurso em streaming diretamente para arquivo em disco, abortando e
     * removendo o arquivo parcial caso o volume ultrapasse $maxBytes.
     *
     * @param array<string, string> $headers
     * @throws TransportException Em falha de rede, estouro de tamanho ou violação de allowlist.
     */
    public function download(
        string $url,
        string $destinationFile,
        int $maxBytes,
        array $headers = [],
        int $timeoutSeconds = 60,
    ): HttpResponse;
}
