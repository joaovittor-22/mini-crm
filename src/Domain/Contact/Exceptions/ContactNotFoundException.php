<?php

declare(strict_types=1);

namespace Domain\Contact\Exceptions;

use DomainException;

/**
 * Lançada pelos repositórios quando um contato buscado não é encontrado.
 */
final class ContactNotFoundException extends DomainException
{
    public static function withId(int $id): self
    {
        return new self("Contato com ID #{$id} não foi encontrado.");
    }
}
