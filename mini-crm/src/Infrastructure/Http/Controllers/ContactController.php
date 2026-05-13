<?php

declare(strict_types=1);

namespace Infrastructure\Http\Controllers;

use Application\Contact\DTOs\CreateContactDTO;
use Application\Contact\DTOs\UpdateContactDTO;
use Application\Contact\UseCases\CalculateContactScoreUseCase;
use Application\Contact\UseCases\CreateContactUseCase;
use Application\Contact\UseCases\DeleteContactUseCase;
use Application\Contact\UseCases\UpdateContactUseCase;
use Domain\Contact\Contracts\ContactRepositoryInterface;
use Domain\Contact\Exceptions\ContactCannotBeProcessedException;
use Domain\Contact\Exceptions\ContactNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Infrastructure\Http\Requests\CreateContactRequest;
use Infrastructure\Http\Requests\UpdateContactRequest;
use Infrastructure\Http\Resources\ContactResource;
use Infrastructure\Queue\Jobs\ProcessContactScoreJob;

/**
 * Controller HTTP de Contatos — camada de Infraestrutura.
 *
 * Este controller é fino (thin controller): não contém nenhuma lógica de negócio.
 * Sua única responsabilidade é:
 * 1. Receber a requisição HTTP.
 * 2. Construir o DTO a partir dos dados validados.
 * 3. Delegar ao Use Case correto.
 * 4. Retornar a resposta HTTP formatada via API Resource.
 *
 * Toda lógica de negócio está nos Use Cases e na Entidade de Domínio.
 */
final class ContactController
{
    public function __construct(
        private readonly CreateContactUseCase $createContact,
        private readonly UpdateContactUseCase $updateContact,
        private readonly DeleteContactUseCase $deleteContact,
        private readonly ContactRepositoryInterface $repository,
    ) {}

    /**
     * GET /api/contacts
     * Lista todos os contatos com paginação.
     */
    public function index(Request $request): JsonResponse
    {
        $page    = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 15);

        $result = $this->repository->paginate($page, $perPage);

        return response()->json([
            'data' => ContactResource::collection(collect($result['data'])),
            'meta' => [
                'total'        => $result['total'],
                'per_page'     => $result['per_page'],
                'current_page' => $result['current_page'],
                'last_page'    => $result['last_page'],
            ],
        ]);
    }

    /**
     * POST /api/contacts
     * Cria um novo contato.
     */
    public function store(CreateContactRequest $request): JsonResponse
    {
        $dto = new CreateContactDTO(
            name: $request->input('name'),
            email: $request->input('email'),
            phone: $request->input('phone'),
        );

        $contact = $this->createContact->execute($dto);

        return response()->json(
            new ContactResource($contact),
            JsonResponse::HTTP_CREATED
        );
    }

    /**
     * GET /api/contacts/{id}
     * Exibe um contato específico.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $contact = $this->repository->findById($id);
        } catch (ContactNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_NOT_FOUND);
        }

        return response()->json(new ContactResource($contact));
    }

    /**
     * PUT /api/contacts/{id}
     * Atualiza um contato existente.
     */
    public function update(UpdateContactRequest $request, int $id): JsonResponse
    {
        try {
            $dto = new UpdateContactDTO(
                id: $id,
                name: $request->input('name'),
                email: $request->input('email'),
                phone: $request->input('phone'),
            );

            $contact = $this->updateContact->execute($dto);
        } catch (ContactNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_NOT_FOUND);
        }

        return response()->json(new ContactResource($contact));
    }

    /**
     * DELETE /api/contacts/{id}
     * Realiza soft delete de um contato.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->deleteContact->execute($id);
        } catch (ContactNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_NOT_FOUND);
        }

        return response()->json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * POST /api/contacts/{id}/process-score
     * Enfileira o processamento assíncrono do score do contato.
     */
    public function processScore(int $id): JsonResponse
    {
        try {
            $contact = $this->repository->findById($id);
        } catch (ContactNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_NOT_FOUND);
        }

        try {
            // Apenas enfileira — a resposta é imediata (202 Accepted)
            ProcessContactScoreJob::dispatch($id);
        } catch (ContactCannotBeProcessedException $e) {
            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'message' => 'O processamento do score foi enfileirado com sucesso.',
            'contact' => new ContactResource($contact),
        ], JsonResponse::HTTP_ACCEPTED);
    }
}
