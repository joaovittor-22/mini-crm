<?php

declare(strict_types=1);

namespace Application\Contact\UseCases;

use Application\Contact\DTOs\CreateContactDTO;
use Domain\Contact\Contracts\ContactRepositoryInterface;
use Domain\Contact\Entities\Contact;
use Domain\Contact\ValueObjects\Email;
use Domain\Contact\ValueObjects\Phone;

/**
 * Use Case: Criar um novo Contato.
 *
 * Orquestra a criação de um contato, delegando:
 * - Construção dos Value Objects ao domínio.
 * - Persistência ao repositório (via interface).
 *
 * Este Use Case não sabe nada sobre HTTP, banco de dados ou filas.
 * Ele recebe um DTO validado e retorna a entidade criada.
 */
final class CreateContactUseCase
{
    public function __construct(
        private readonly ContactRepositoryInterface $repository,
    ) {}

    /**
     * @throws \Domain\Contact\Exceptions\InvalidEmailException  se o e-mail for inválido.
     * @throws \Domain\Contact\Exceptions\InvalidPhoneException  se o telefone for inválido.
     */
    public function execute(CreateContactDTO $dto): Contact
    {
        // Cria os Value Objects — aqui a validação de domínio ocorre
        $email = Email::fromString($dto->email);
        $phone = Phone::fromString($dto->phone);

        // Cria a entidade com ID temporário 0; o repositório atribui o ID real
        $contact = Contact::create(
            id: 0,
            name: $dto->name,
            email: $email,
            phone: $phone,
        );

        // Persiste e retorna a entidade com o ID gerado pelo banco
        return $this->repository->save($contact);
    }
}
