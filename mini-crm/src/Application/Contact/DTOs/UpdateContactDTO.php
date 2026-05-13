<?php

declare(strict_types=1);

namespace Application\Contact\DTOs;

/**
 * Data Transfer Object para atualização de um contato.
 *
 * Os campos são nullable para permitir atualizações parciais (PATCH),
 * embora o endpoint atual seja PUT (todos os campos obrigatórios).
 * Mantemos flexibilidade para evolução futura.
 */
final readonly class UpdateContactDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $phone,
    ) {}
}
