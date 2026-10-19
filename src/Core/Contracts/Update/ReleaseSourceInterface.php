<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts\Update;

use DomainSystem\Core\Update\Exception\SourceUnavailableException;
use DomainSystem\Core\Update\Exception\TransportException;
use DomainSystem\Core\Update\Value\DownloadedPackage;
use DomainSystem\Core\Update\Value\ReleaseInfo;
use DomainSystem\Core\Update\Value\UpdateTarget;

/**
 * Contrato para descoberta e download de Releases oficiais do sistema.
 */
interface ReleaseSourceInterface
{
    /**
     * Consulta a última Release estável disponível para o alvo informado.
     * Devolve `null` caso o servidor responda `304 Not Modified` para o `$knownEtag`.
     *
     * @throws SourceUnavailableException Quando a Release, seus metadados ou o asset `.sha256` são inválidos ou estão indisponíveis.
     * @throws TransportException Quando ocorre erro de transporte HTTP.
     */
    public function latest(UpdateTarget $target, ?string $knownEtag = null): ?ReleaseInfo;

    /**
     * Faz o download do pacote ZIP da Release para o diretório de destino.
     *
     * @throws TransportException Em caso de erro de rede, timeout ou tamanho excedido.
     */
    public function fetchPackage(ReleaseInfo $release, string $destinationDir): DownloadedPackage;
}
