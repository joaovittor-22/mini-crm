<?php

declare(strict_types=1);

namespace Application\Contact\UseCases;

use Application\Contact\DTOs\UpdateContactDTO;
use Domain\Contact\Contracts\ContactRepositoryInterface;
use Domain\Contact\Entities\Contact;
use Domain\Contact\ValueObjects\Email;
use Domain\Contact\ValueObjects\Phone;

/**
 * Use Case: Atualizar um Contato existente.
 *
 * Busca o contato pelo ID, atualiza os dados e persiste.
 * A atualização é feita criando uma nova entidade com os dados atualizados
 * (preservando score, status e processedAt do contato original).
 */
final class UpdateContactUseCase
{
    public function __construct(
        private readonly ContactRepositoryInterface $repository,
    ) {}

    /**
     * @throws \Domain\Contact\Exceptions\ContactNotFoundException se o contato não existir.
     * @throws \Domain\Contact\Exceptions\InvalidEmailException    se o e-mail for inválido.
     * @throws \Domain\Contact\Exceptions\InvalidPhoneException    se o telefone for inválido.
     */
    public function execute(UpdateContactDTO $dto): Contact
    {
        // Busca o contato existente — lança ContactNotFoundException se não encontrado
        $existing = $this->repository->findById($dto->id);

        // Cria Value Objects com os novos dados
        $email = Email::fromString($dto->email);
        $phone = Phone::fromString($dto->phone);

        // Reconstituímos a entidade preservando campos imutáveis do negócio (score, status, processedAt)
        $updated = Contact::reconstitute(
            id: $existing->id(),
            name: $dto->name,
            email: $email,
            phone: $phone,
            score: $existing->score(),
            status: $existing->status(),
            processedAt: $existing->processedAt(),
        );

        return $this->repository->save($updated);
    }
}
