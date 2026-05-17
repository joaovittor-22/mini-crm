<?php

declare(strict_types=1);

namespace Application\Contact\DTOs;

/**
 * Data Transfer Object para criação de um contato.
 *
 * DTOs transportam dados entre camadas sem lógica de negócio.
 * São simples e imutáveis. O Form Request (infraestrutura) os constrói
 * com dados validados e os passa ao Use Case.
 */
final readonly class CreateContactDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $phone,
    ) {}
}
