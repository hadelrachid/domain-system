<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Styles\Values;

use InvalidArgumentException;
use JsonSerializable;

/**
 * Value Object imutável que representa uma dimensão CSS tipada (largura, altura, margem, etc.).
 * Segue Ring 0 e valida todas as unidades sem risco de injeção CSS.
 */
final class DimensionValue implements JsonSerializable
{
    public function __construct(
        private readonly float $amount,
        private readonly CssUnit $unit
    ) {}

    public static function px(float $amount): self
    {
        return new self($amount, CssUnit::PX);
    }

    public static function percent(float $amount): self
    {
        return new self($amount, CssUnit::PERCENT);
    }

    public static function rem(float $amount): self
    {
        return new self($amount, CssUnit::REM);
    }

    public static function em(float $amount): self
    {
        return new self($amount, CssUnit::EM);
    }

    public static function vh(float $amount): self
    {
        return new self($amount, CssUnit::VH);
    }

    public static function vw(float $amount): self
    {
        return new self($amount, CssUnit::VW);
    }

    public static function cm(float $amount): self
    {
        return new self($amount, CssUnit::CM);
    }

    public static function auto(): self
    {
        return new self(0.0, CssUnit::AUTO);
    }

    public static function fitContent(): self
    {
        return new self(0.0, CssUnit::FIT_CONTENT);
    }

    /**
     * Faz o parse seguro de uma string CSS dimensional (ex: "100vh", "50%", "24px", "2.5cm", "auto").
     */
    public static function fromString(string $raw): self
    {
        $clean = trim($raw);
        if ($clean === '' || $clean === '0') {
            return new self(0.0, CssUnit::PX);
        }

        $lower = strtolower($clean);

        // Keywords sem número
        if ($lower === 'auto') return self::auto();
        if ($lower === 'fit-content') return self::fitContent();
        if ($lower === 'min-content') return new self(0.0, CssUnit::MIN_CONTENT);
        if ($lower === 'max-content') return new self(0.0, CssUnit::MAX_CONTENT);

        // Extrai número e unidade via regex seguro
        if (preg_match('/^([+-]?[0-9]+(?:\.[0-9]+)?)\s*([a-z%]*)$/i', $clean, $matches)) {
            $amount = (float)$matches[1];
            $unitStr = strtolower(trim($matches[2]));

            if ($unitStr === '') {
                $unit = CssUnit::PX; // Default para pixels
            } else {
                $unit = CssUnit::tryFromString($unitStr);
                if ($unit === null) {
                    throw new InvalidArgumentException("Unidade CSS não suportada: '{$unitStr}'.");
                }
            }

            return new self($amount, $unit);
        }

        throw new InvalidArgumentException("Valor de dimensão CSS inválido: '{$raw}'.");
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getUnit(): CssUnit
    {
        return $this->unit;
    }

    public function toCss(): string
    {
        if (!$this->unit->isNumeric()) {
            return $this->unit->value;
        }

        // Formata float removendo zeros à direita desnecessários (ex: 2.0 -> 2, 2.5 -> 2.5)
        $formatted = (string)(fmod($this->amount, 1.0) === 0.0 ? (int)$this->amount : $this->amount);
        return $formatted . $this->unit->value;
    }

    public function __toString(): string
    {
        return $this->toCss();
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'unit'   => $this->unit->value,
            'css'    => $this->toCss(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
