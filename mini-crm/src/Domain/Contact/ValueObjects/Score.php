<?php

declare(strict_types=1);

namespace Domain\Contact\ValueObjects;

use Domain\Contact\Exceptions\InvalidScoreException;

/**
 * Value Object que representa a pontuação (score) de um contato.
 *
 * Garante que o score seja sempre um inteiro não-negativo.
 * A imutabilidade é garantida: operações de soma retornam nova instância.
 */
final class Score
{
    private readonly int $value;

    /**
     * @throws InvalidScoreException quando o valor for negativo.
     */
    public function __construct(int $value = 0)
    {
        if ($value < 0) {
            throw new InvalidScoreException("O score não pode ser negativo. Valor recebido: {$value}");
        }

        $this->value = $value;
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    public function value(): int
    {
        return $this->value;
    }

    /**
     * Retorna uma nova instância com o valor somado.
     * Imutabilidade preservada.
     */
    public function add(int $points): self
    {
        return new self($this->value + $points);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
