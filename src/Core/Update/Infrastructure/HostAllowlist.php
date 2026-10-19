<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Infrastructure;

final readonly class HostAllowlist
{
    /**
     * @var list<string>
     */
    private array $patterns;

    /**
     * @param list<string>|null $patterns
     */
    public function __construct(?array $patterns = null)
    {
        $default = [
            'api.github.com',
            'github.com',
            'objects.githubusercontent.com',
            'release-assets.githubusercontent.com',
            '*.githubusercontent.com',
        ];

        $normalized = [];
        foreach ($patterns ?? $default as $pattern) {
            $clean = strtolower(trim($pattern));
            if ($clean !== '') {
                $normalized[] = $clean;
            }
        }

        $this->patterns = array_values(array_unique($normalized));
    }

    public function isAllowed(string $host): bool
    {
        $candidate = strtolower(trim($host, ". \t\n\r\0\x0B"));
        if ($candidate === '') {
            return false;
        }

        foreach ($this->patterns as $pattern) {
            if ($pattern === $candidate) {
                return true;
            }

            if (str_starts_with($pattern, '*.')) {
                $suffix = substr($pattern, 1); // ex.: ".githubusercontent.com"
                $bareDomain = substr($pattern, 2); // ex.: "githubusercontent.com"
                if (
                    $candidate !== $bareDomain
                    && str_ends_with($candidate, $suffix)
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public function patterns(): array
    {
        return $this->patterns;
    }
}
