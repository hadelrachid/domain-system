<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts\Update;

use DomainSystem\Core\Update\Exception\InvalidManifestException;
use DomainSystem\Core\Update\Value\UpdateManifest;

/**
 * Contrato de leitura e validação estrita do arquivo `manifest.json` contido no pacote de atualização.
 */
interface UpdateManifestParserInterface
{
    /**
     * Lista as versões de protocolo de manifest suportadas por este atualizador (ex.: `[1]`).
     *
     * @return list<int>
     */
    public function supportedProtocols(): array;

    /**
     * Faz o parse e valida estritamente o JSON do manifest.
     * Qualquer campo ausente, tipo incorreto ou protocolo não suportado lança exceção.
     *
     * @throws InvalidManifestException Quando o JSON é inválido, incompleto ou de protocolo desconhecido.
     */
    public function parse(string $json): UpdateManifest;
}
