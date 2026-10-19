<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts\Update;

use InvalidArgumentException;

/**
 * Contrato de validação, normalização e comparação SemVer 2.0.0.
 */
interface VersionComparatorInterface
{
    /**
     * Verifica se a string segue SemVer estrito (`MAJOR.MINOR.PATCH` com `-prerelease` opcional).
     */
    public function isValid(string $version): bool;

    /**
     * Normaliza uma versão ou tag (removendo prefixo `v`/`V` inicial) e valida o formato SemVer.
     *
     * @throws InvalidArgumentException Se a versão não for SemVer válida.
     */
    public function normalize(string $versionOrTag): string;

    /**
     * Compara duas versões SemVer válidas: devolve -1 se $a < $b, 0 se $a == $b, 1 se $a > $b.
     *
     * @throws InvalidArgumentException Se alguma das versões for inválida.
     */
    public function compare(string $a, string $b): int;

    /**
     * Devolve `true` se a versão candidata ($candidate) for estritamente maior que a instalada ($installed).
     *
     * @throws InvalidArgumentException Se alguma das versões for inválida.
     */
    public function isNewer(string $candidate, string $installed): bool;
}
