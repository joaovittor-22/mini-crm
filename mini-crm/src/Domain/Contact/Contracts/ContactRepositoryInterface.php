<?php

declare(strict_types=1);

namespace Domain\Contact\Contracts;

use Domain\Contact\Entities\Contact;

/**
 * Interface (Port) do Repositório de Contatos.
 *
 * Define o contrato que qualquer implementação de persistência deve seguir.
 * Os Use Cases dependem desta interface, nunca da implementação concreta (Eloquent).
 * Isso garante a Inversão de Dependência (princípio D do SOLID) e permite
 * substituir a infraestrutura de persistência sem alterar o domínio.
 *
 * A implementação concreta (EloquentContactRepository) vive na camada de Infrastructure.
 */
interface ContactRepositoryInterface
{
    /**
     * Persiste um novo contato ou atualiza um existente.
     *
     * @return Contact A entidade atualizada após persistência (pode ter IDs gerados).
     */
    public function save(Contact $contact): Contact;

    /**
     * Busca um contato pelo seu identificador único.
     *
     * @throws \Domain\Contact\Exceptions\ContactNotFoundException quando não encontrado.
     */
    public function findById(int $id): Contact;

    /**
     * Busca todos os contatos com paginação.
     *
     * @param int $page    Número da página (1-based).
     * @param int $perPage Quantidade de itens por página.
     *
     * @return array{data: Contact[], total: int, per_page: int, current_page: int, last_page: int}
     */
    public function paginate(int $page = 1, int $perPage = 15): array;

    /**
     * Remove (soft delete) um contato pelo identificador.
     *
     * @throws \Domain\Contact\Exceptions\ContactNotFoundException quando não encontrado.
     */
    public function delete(int $id): void;

    /**
     * Verifica se já existe um contato cadastrado com o e-mail informado.
     */
    public function existsByEmail(string $email, ?int $excludeId = null): bool;
}
