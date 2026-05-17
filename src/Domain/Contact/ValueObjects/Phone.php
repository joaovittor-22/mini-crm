<?php

declare(strict_types=1);

namespace Domain\Contact\ValueObjects;

use Domain\Contact\Exceptions\InvalidPhoneException;

/**
 * Value Object que encapsula o número de telefone de um contato.
 *
 * Responsável por normalizar, validar e extrair informações relevantes
 * do telefone, como o código DDD, que é usado nas regras de pontuação.
 *
 * O armazenamento interno é sempre feito apenas com dígitos numéricos,
 * independentemente do formato de entrada.
 */
final class Phone
{
    /**
     * DDDs do estado de São Paulo, conforme ANATEL.
     * Utilizados na regra de pontuação de +20 pontos.
     */
    private const SAO_PAULO_AREA_CODES = [11, 12, 13, 14, 15, 16, 17, 18, 19];

    /**
     * Número normalizado (somente dígitos).
     */
    private readonly string $digits;

    /**
     * @throws InvalidPhoneException quando o telefone não puder ser normalizado para um formato válido.
     */
    public function __construct(string $value)
    {
        // Remove tudo que não for dígito
        $digits = preg_replace('/\D/', '', $value);

        // Remove o código do país +55 se presente
        if (strlen($digits) > 11 && str_starts_with($digits, '55')) {
            $digits = substr($digits, 2);
        }

        // Valida comprimento: 10 dígitos (fixo) ou 11 dígitos (celular com 9)
        if (! in_array(strlen($digits), [10, 11], strict: true)) {
            throw new InvalidPhoneException(
                "O telefone '{$value}' não possui um formato válido (esperado 10 ou 11 dígitos)."
            );
        }

        $this->digits = $digits;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * Retorna o número completo apenas com dígitos.
     */
    public function value(): string
    {
        return $this->digits;
    }

    /**
     * Retorna o número formatado no padrão brasileiro: (11) 99999-9999
     */
    public function formatted(): string
    {
        if (strlen($this->digits) === 11) {
            return sprintf(
                '(%s) %s-%s',
                substr($this->digits, 0, 2),
                substr($this->digits, 2, 5),
                substr($this->digits, 7)
            );
        }

        return sprintf(
            '(%s) %s-%s',
            substr($this->digits, 0, 2),
            substr($this->digits, 2, 4),
            substr($this->digits, 6)
        );
    }

    /**
     * Extrai o código DDD (primeiros 2 dígitos).
     */
    public function areaCode(): int
    {
        return (int) substr($this->digits, 0, 2);
    }

    /**
     * Verifica se o DDD pertence ao estado de São Paulo.
     */
    public function hasSaoPauloAreaCode(): bool
    {
        return in_array($this->areaCode(), self::SAO_PAULO_AREA_CODES, strict: true);
    }

    /**
     * Verifica se o telefone possui algum DDD válido (qualquer estado brasileiro).
     * Consideramos válido qualquer DDD entre 11 e 99.
     */
    public function hasValidAreaCode(): bool
    {
        $code = $this->areaCode();

        return $code >= 11 && $code <= 99;
    }

    public function equals(self $other): bool
    {
        return $this->digits === $other->digits;
    }

    public function __toString(): string
    {
        return $this->formatted();
    }
}
