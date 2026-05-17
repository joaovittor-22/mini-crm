<?php

declare(strict_types=1);

namespace Application\Contact\UseCases;

use Domain\Contact\Contracts\ContactRepositoryInterface;
use Domain\Contact\Entities\Contact;
use Domain\Contact\Events\ContactScoreProcessed;
use Domain\Contact\Services\ScoreCalculatorService;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Throwable;

/**
 * Use Case: Calcular e aplicar o Score de um Contato.
 *
 * Este Use Case é executado dentro de um Job (fila assíncrona).
 * Ele orquestra todo o fluxo de processamento de score:
 *
 * 1. Busca o contato e marca como 'processing'.
 * 2. Delega o cálculo ao Domain Service (ScoreCalculatorService).
 * 3. Aplica o score calculado à entidade e marca como 'active'.
 * 4. Persiste o resultado.
 * 5. Dispara o evento de domínio ContactScoreProcessed.
 *
 * Em caso de falha, marca o contato como 'failed' e relança a exceção.
 *
 * Note que o sleep(2) emula latência de processamento pesado,
 * validando que o fluxo assíncrono está funcionando corretamente.
 */
final class CalculateContactScoreUseCase
{
    public function __construct(
        private readonly ContactRepositoryInterface $repository,
        private readonly ScoreCalculatorService $calculator,
        private readonly EventDispatcher $events,
    ) {}

    /**
     * @throws \Domain\Contact\Exceptions\ContactNotFoundException          se o contato não existir.
     * @throws \Domain\Contact\Exceptions\ContactCannotBeProcessedException se já estiver em processamento.
     */
    public function execute(int $contactId): Contact
    {
        // Carrega o contato do banco de dados
        $contact = $this->repository->findById($contactId);

        // Inicia o processamento — lança exceção se status não permitir
        $contact->startProcessing();
        $this->repository->save($contact);

        try {
            // Simula carga de processamento pesado (pode ser removido em produção real)
            sleep(2);

            // Delega o cálculo ao Domain Service com as Strategies
            $score = $this->calculator->calculate($contact);

            // Aplica o resultado à entidade (muda status para 'active' e preenche processedAt)
            $contact->applyScore($score);

            // Persiste o estado final
            $this->repository->save($contact);

            // Dispara o evento de domínio para que Listeners possam reagir
            $this->events->dispatch(new ContactScoreProcessed($contact));

        } catch (Throwable $exception) {
            // Em caso de qualquer falha, marca o contato como 'failed' e persiste
            $contact->markAsFailed();
            $this->repository->save($contact);

            // Relança para que o Job possa registrar a falha e tentar novamente
            throw $exception;
        }

        return $contact;
    }
}
