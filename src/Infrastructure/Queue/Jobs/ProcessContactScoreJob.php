<?php

declare(strict_types=1);

namespace Infrastructure\Queue\Jobs;

use Application\Contact\UseCases\CalculateContactScoreUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Job responsável por executar o processamento de score de forma assíncrona.
 *
 * Este Job é apenas um wrapper de infraestrutura: recebe o ID do contato
 * da fila e delega toda a lógica ao CalculateContactScoreUseCase.
 *
 * Configurações:
 * - Fila: contacts (separada da fila padrão para priorização futura)
 * - Tentativas: 3 (com backoff exponencial em caso de falha)
 * - Timeout: 120 segundos (inclui os 2 segundos de sleep simulado)
 */
final class ProcessContactScoreJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Número máximo de tentativas em caso de falha.
     */
    public int $tries = 3;

    /**
     * Tempo máximo de execução em segundos.
     */
    public int $timeout = 120;

    public function __construct(
        private readonly int $contactId,
    ) {
        // Direciona o Job para a fila 'contacts'
        $this->onQueue('contacts');
    }

    /**
     * Executa o processamento do score delegando ao Use Case.
     */
    public function handle(CalculateContactScoreUseCase $useCase): void
    {
        Log::channel('contact')->info("Iniciando processamento de score para o contato #{$this->contactId}.");

        $useCase->execute($this->contactId);

        Log::channel('contact')->info("Processamento de score concluído para o contato #{$this->contactId}.");
    }

    /**
     * Chamado quando o Job falha após todas as tentativas.
     * Garante que o contato não fique preso em 'processing'.
     */
    public function failed(Throwable $exception): void
    {
        Log::channel('contact')->error(
            "Falha definitiva no processamento do contato #{$this->contactId}: {$exception->getMessage()}"
        );
    }
}
