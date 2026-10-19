<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts\Update;

use DomainSystem\Core\Update\Exception\InvalidManifestException;
use DomainSystem\Core\Update\Exception\PackageIntegrityException;
use DomainSystem\Core\Update\Value\DownloadedPackage;
use DomainSystem\Core\Update\Value\VerifiedPackage;

/**
 * Contrato de verificação criptográfica (SHA-256) e estrutural do pacote baixado.
 *
 * Obrigações da implementação:
 * - Conferir o SHA-256 do arquivo ZIP contra `ReleaseInfo::sha256`.
 * - Extrair o pacote em diretório de quarentena com proteção anti Zip-Slip e anti Zip-Bomb.
 * - Ler o `manifest.json` e conferir tamanho e SHA-256 de cada arquivo listado.
 * - Rejeitar arquivos extras fora do manifest, arquivos faltantes ou caminhos fora das raízes permitidas.
 */
interface UpdatePackageVerifierInterface
{
    /**
     * Verifica a integridade do pacote e de todos os arquivos extraídos em quarentena.
     *
     * @throws PackageIntegrityException Quando o hash do ZIP, de algum arquivo ou a estrutura da árvore é inválida.
     * @throws InvalidManifestException Quando o `manifest.json` está ausente ou é inválido.
     */
    public function verify(DownloadedPackage $package): VerifiedPackage;
}
