<?php

declare(strict_types=1);

namespace Application\Contact\UseCases;

use Domain\Contact\Contracts\ContactRepositoryInterface;

/**
 * Use Case: Excluir (soft delete) um Contato.
 *
 * A exclusão é lógica (soft delete): o registro permanece no banco
 * com o campo deleted_at preenchido, mas não aparece nas listagens.
 */
final class DeleteContactUseCase
{
    public function __construct(
        private readonly ContactRepositoryInterface $repository,
    ) {}

    /**
     * @throws \Domain\Contact\Exceptions\ContactNotFoundException se o contato não existir.
     */
    public function execute(int $id): void
    {
        $this->repository->delete($id);
    }
}
