<?php

declare(strict_types=1);

namespace Domain\Contact\ValueObjects;

use Domain\Contact\Exceptions\InvalidEmailException;

/**
 * Value Object que encapsula o endereço de e-mail de um contato.
 *
 * Value Objects são imutáveis. Toda a lógica relacionada ao e-mail
 * (validação, extração de domínio, verificação de tipo corporativo)
 * vive aqui, longe da entidade e longe da infraestrutura.
 *
 * A igualdade entre dois Email VOs é determinada pelo valor, não pela identidade.
 */
final class Email
{
    /**
     * Lista de domínios de e-mail gratuitos/pessoais conhecidos.
     * Utilizada na regra de pontuação de score.
     */
    private const FREE_DOMAINS = [
        'gmail.com',
        'googlemail.com',
        'hotmail.com',
        'hotmail.com.br',
        'outlook.com',
        'live.com',
        'yahoo.com',
        'yahoo.com.br',
        'msn.com',
        'bol.com.br',
        'terra.com.br',
        'uol.com.br',
        'ig.com.br',
    ];

    private readonly string $value;

    /**
     * @throws InvalidEmailException quando o formato do e-mail for inválido.
     */
    public function __construct(string $value)
    {
        $normalized = strtolower(trim($value));

        if (! filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException("O e-mail '{$value}' possui formato inválido.");
        }

        $this->value = $normalized;
    }

    /**
     * Factory method alternativo ao construtor direto.
     */
    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * Retorna o endereço de e-mail completo em letras minúsculas.
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Extrai apenas o domínio do e-mail (ex: "empresa.com.br").
     */
    public function domain(): string
    {
        return substr($this->value, strpos($this->value, '@') + 1);
    }

    /**
     * Verifica se o e-mail pertence a um domínio corporativo.
     * Domínios listados em FREE_DOMAINS são considerados pessoais.
     */
    public function isCorporate(): bool
    {
        return ! in_array($this->domain(), self::FREE_DOMAINS, strict: true);
    }

    /**
     * Verifica se o domínio do e-mail termina com a extensão '.br'.
     */
    public function hasBrazilianDomain(): bool
    {
        return str_ends_with($this->domain(), '.br');
    }

    /**
     * Compara dois Value Objects de Email por valor.
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
