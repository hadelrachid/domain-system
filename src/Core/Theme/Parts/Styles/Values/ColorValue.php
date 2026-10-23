<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Styles\Values;

use InvalidArgumentException;
use JsonSerializable;

/**
 * Value Object imutável que representa uma cor CSS tipada (Design Token, Hex, RGBA, Gradiente ou Transparente).
 * Garante a filosofia "Token-First" e higienização estrita contra injeção de CSS (SOLID - Ring 0).
 */
final class ColorValue implements JsonSerializable
{
    public const TYPE_TOKEN       = 'token';
    public const TYPE_HEX         = 'hex';
    public const TYPE_RGB         = 'rgb';
    public const TYPE_GRADIENT    = 'gradient';
    public const TYPE_TRANSPARENT = 'transparent';

    public function __construct(
        private readonly string $type,
        private readonly string $value,
        private readonly ?string $tokenName = null
    ) {}

    public static function fromToken(string $tokenName, ?string $fallback = null): self
    {
        $clean = strtolower(trim($tokenName));
        $clean = preg_replace('/^var\(--|\)$/', '', $clean) ?? $clean;

        if (str_contains($clean, ',')) {
            $parts = explode(',', $clean, 2);
            $clean = trim($parts[0]);
            if ($fallback === null) {
                $fallback = trim($parts[1]);
            }
        }

        $clean = ltrim($clean, '-');

        if (!preg_match('/^[a-z0-9_-]+$/', $clean)) {
            throw new InvalidArgumentException("Nome de Design Token inválido: '{$tokenName}'.");
        }

        if ($fallback !== null && $fallback !== '') {
            $sanitizedFallback = preg_replace('/[;<>{}\\\\]/', '', $fallback) ?? '';
            return new self(self::TYPE_TOKEN, "var(--{$clean}, {$sanitizedFallback})", $clean);
        }

        return new self(self::TYPE_TOKEN, "var(--{$clean})", $clean);
    }

    public static function fromHex(string $hex): self
    {
        $clean = trim($hex);
        if (!preg_match('/^#([a-f0-9]{3,4}|[a-f0-9]{6}|[a-f0-9]{8})$/i', $clean)) {
            throw new InvalidArgumentException("Formato hexadecimal de cor inválido: '{$hex}'.");
        }

        return new self(self::TYPE_HEX, $clean);
    }

    public static function fromRgba(int $r, int $g, int $b, float $a = 1.0): self
    {
        $r = max(0, min(255, $r));
        $g = max(0, min(255, $g));
        $b = max(0, min(255, $b));
        $a = max(0.0, min(1.0, $a));

        $val = $a < 1.0 ? "rgba({$r}, {$g}, {$b}, {$a})" : "rgb({$r}, {$g}, {$b})";
        return new self(self::TYPE_RGB, $val);
    }

    public static function transparent(): self
    {
        return new self(self::TYPE_TRANSPARENT, 'transparent');
    }

    public static function fromGradient(string $gradient): self
    {
        $clean = trim($gradient);
        // Higieniza contra injeção
        if (preg_match('/[;<>{}\\\\]/', $clean) || !preg_match('/^(linear|radial|conic)-gradient\(.+\)$/i', $clean)) {
            throw new InvalidArgumentException("Declaração de gradiente CSS inválida.");
        }

        return new self(self::TYPE_GRADIENT, $clean);
    }

    /**
     * Faz o parse automático de uma string de cor (Token, Hex, RGBA, Gradiente ou Palavra-chave).
     */
    public static function fromString(string $raw): self
    {
        $clean = trim($raw);
        if ($clean === '' || $clean === 'transparent') {
            return self::transparent();
        }

        // Caso 1: Design Token explícito ex: var(--primary)
        if (str_starts_with($clean, 'var(--') && str_ends_with($clean, ')')) {
            $token = substr($clean, 6, -1);
            return self::fromToken($token);
        }

        // Caso 2: Hexadecimal (#fff, #45f3ff)
        if (str_starts_with($clean, '#')) {
            return self::fromHex($clean);
        }

        // Caso 3: Nome de token sem var() ex: "primary", "secondary", "background"
        $knownTokens = ['primary', 'secondary', 'tertiary', 'background', 'surface', 'text_main', 'text_muted', 'border', 'accent'];
        if (in_array(strtolower($clean), $knownTokens, true)) {
            return self::fromToken($clean);
        }

        // Caso 4: RGB / RGBA / HSL
        if (preg_match('/^(rgba?|hsla?)\([0-9.,% ]+\)$/i', $clean)) {
            return new self(self::TYPE_RGB, $clean);
        }

        // Caso 5: Gradiente
        if (preg_match('/^(linear|radial|conic)-gradient\(/i', $clean)) {
            return self::fromGradient($clean);
        }

        // Caso 6: Palavras-chave CSS permitidas
        if (in_array(strtolower($clean), ['currentcolor', 'inherit', 'initial'], true)) {
            return new self(self::TYPE_TRANSPARENT, strtolower($clean));
        }

        throw new InvalidArgumentException("Formato de cor não suportado: '{$raw}'.");
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isToken(): bool
    {
        return $this->type === self::TYPE_TOKEN;
    }

    public function getTokenName(): ?string
    {
        return $this->tokenName;
    }

    public function toCss(): string
    {
        return $this->value;
    }

    public function getCssValue(): string
    {
        return $this->toCss();
    }

    public function __toString(): string
    {
        return $this->toCss();
    }

    public function toArray(): array
    {
        return [
            'type'       => $this->type,
            'value'      => $this->value,
            'token_name' => $this->tokenName,
            'is_token'   => $this->isToken(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
