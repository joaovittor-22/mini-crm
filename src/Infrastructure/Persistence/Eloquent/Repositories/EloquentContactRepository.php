<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Domain\Contact\Contracts\ContactRepositoryInterface;
use Domain\Contact\Entities\Contact;
use Domain\Contact\Exceptions\ContactNotFoundException;
use Domain\Contact\ValueObjects\Email;
use Domain\Contact\ValueObjects\Phone;
use Domain\Contact\ValueObjects\Score;
use Infrastructure\Persistence\Eloquent\Models\ContactModel;

/**
 * Implementação Eloquent do Repositório de Contatos.
 *
 * Esta classe é o Adaptador (no sentido de Arquitetura Hexagonal) entre
 * o Domínio e o banco de dados. Ela implementa a interface ContactRepositoryInterface
 * e é responsável por:
 *
 * - Converter Entidades de Domínio em registros Eloquent (para persistência).
 * - Converter registros Eloquent em Entidades de Domínio (hidratação).
 *
 * Todo acesso ao Eloquent passa por esta classe. Use Cases e Domain Services
 * nunca importam ou conhecem o modelo Eloquent.
 */
final class EloquentContactRepository implements ContactRepositoryInterface
{
    public function __construct(
        private readonly ContactModel $model,
    ) {}

    /**
     * Persiste a entidade. Se o ID for 0 (novo contato), cria um novo registro.
     * Caso contrário, atualiza o registro existente.
     */
    public function save(Contact $contact): Contact
    {
        if ($contact->id() === 0) {
            $record = $this->model->newQuery()->create([
                'name'         => $contact->name(),
                'email'        => $contact->email()->value(),
                'phone'        => $contact->phone()->value(),
                'score'        => $contact->score()->value(),
                'status'       => $contact->status()->value,
                'processed_at' => $contact->processedAt()?->format('Y-m-d H:i:s'),
            ]);
        } else {
            $record = $this->model->newQuery()->findOrFail($contact->id());
            $record->update([
                'name'         => $contact->name(),
                'email'        => $contact->email()->value(),
                'phone'        => $contact->phone()->value(),
                'score'        => $contact->score()->value(),
                'status'       => $contact->status()->value,
                'processed_at' => $contact->processedAt()?->format('Y-m-d H:i:s'),
            ]);
        }

        // Retorna a entidade reconstituída com o ID correto (gerado pelo banco)
        return $this->toEntity($record->fresh());
    }

    /**
     * Busca um contato pelo ID. Lança ContactNotFoundException se não existir.
     */
    public function findById(int $id): Contact
    {
        $record = $this->model->newQuery()->find($id);

        if ($record === null) {
            throw ContactNotFoundException::withId($id);
        }

        return $this->toEntity($record);
    }

    /**
     * Retorna lista paginada de contatos ordenados pelo mais recente.
     */
    public function paginate(int $page = 1, int $perPage = 15): array
    {
        $paginator = $this->model->newQuery()
            ->orderBy('created_at', 'desc')
            ->paginate(perPage: $perPage, page: $page);

        return [
            'data'         => $paginator->getCollection()->map(fn ($r) => $this->toEntity($r))->all(),
            'total'        => $paginator->total(),
            'per_page'     => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
        ];
    }

    /**
     * Realiza soft delete do contato.
     */
    public function delete(int $id): void
    {
        $record = $this->model->newQuery()->find($id);

        if ($record === null) {
            throw ContactNotFoundException::withId($id);
        }

        $record->delete();
    }

    /**
     * Verifica se já existe um e-mail cadastrado, opcionalmente excluindo um ID.
     */
    public function existsByEmail(string $email, ?int $excludeId = null): bool
    {
        $query = $this->model->newQuery()->where('email', strtolower($email));

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Converte um modelo Eloquent (registro de banco) em Entidade de Domínio.
     *
     * Este método é o coração do repositório: faz a hidratação completa
     * com Value Objects, garantindo que a entidade retornada seja sempre válida.
     */
    private function toEntity(ContactModel $model): Contact
    {
        return Contact::reconstitute(
            id: $model->id,
            name: $model->name,
            email: Email::fromString($model->email),
            phone: Phone::fromString($model->phone),
            score: Score::fromInt($model->score),
            status: $model->status,
            processedAt: $model->processed_at
                ? DateTimeImmutable::createFromMutable($model->processed_at->toDateTime())
                : null,
        );
    }
}
