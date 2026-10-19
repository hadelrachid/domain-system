<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Service;

use DomainSystem\Core\Contracts\Update\VersionComparatorInterface;
use InvalidArgumentException;

final class SemverComparator implements VersionComparatorInterface
{
    private const SEMVER_REGEX = '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-((?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?$/';

    public function isValid(string $version): bool
    {
        return preg_match(self::SEMVER_REGEX, $version) === 1;
    }

    public function normalize(string $versionOrTag): string
    {
        $trimmed = trim($versionOrTag);
        if (str_starts_with($trimmed, 'v') || str_starts_with($trimmed, 'V')) {
            $trimmed = substr($trimmed, 1);
        }

        if (!$this->isValid($trimmed)) {
            throw new InvalidArgumentException("Versão SemVer inválida: {$versionOrTag}");
        }

        return $trimmed;
    }

    public function compare(string $a, string $b): int
    {
        $parsedA = $this->parse($a);
        $parsedB = $this->parse($b);

        foreach (['major', 'minor', 'patch'] as $part) {
            if ($parsedA[$part] !== $parsedB[$part]) {
                return $parsedA[$part] <=> $parsedB[$part];
            }
        }

        $preA = $parsedA['prerelease'];
        $preB = $parsedB['prerelease'];

        if ($preA === null && $preB === null) {
            return 0;
        }
        // Versão sem prerelease tem precedência maior que com prerelease (ex.: 2.4.0 > 2.4.0-beta.1)
        if ($preA === null) {
            return 1;
        }
        if ($preB === null) {
            return -1;
        }

        $idsA = explode('.', $preA);
        $idsB = explode('.', $preB);
        $max = max(count($idsA), count($idsB));

        for ($i = 0; $i < $max; $i++) {
            if (!isset($idsA[$i])) {
                return -1;
            }
            if (!isset($idsB[$i])) {
                return 1;
            }

            $idA = $idsA[$i];
            $idB = $idsB[$i];
            if ($idA === $idB) {
                continue;
            }

            $numA = ctype_digit($idA);
            $numB = ctype_digit($idB);

            if ($numA && $numB) {
                return ((int)$idA) <=> ((int)$idB);
            }
            if ($numA) {
                return -1; // Identificador numérico tem menor precedência que alfanumérico em SemVer 2.0.0
            }
            if ($numB) {
                return 1;
            }

            return strcmp($idA, $idB) < 0 ? -1 : 1;
        }

        return 0;
    }

    public function isNewer(string $candidate, string $installed): bool
    {
        return $this->compare($candidate, $installed) === 1;
    }

    /**
     * @return array{major: int, minor: int, patch: int, prerelease: ?string}
     */
    private function parse(string $version): array
    {
        if (preg_match(self::SEMVER_REGEX, $version, $matches) !== 1) {
            throw new InvalidArgumentException("Versão SemVer estrita inválida: {$version}");
        }

        return [
            'major'      => (int)$matches[1],
            'minor'      => (int)$matches[2],
            'patch'      => (int)$matches[3],
            'prerelease' => isset($matches[4]) && $matches[4] !== '' ? $matches[4] : null,
        ];
    }
}
