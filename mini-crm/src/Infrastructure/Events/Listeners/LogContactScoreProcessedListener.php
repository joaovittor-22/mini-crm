<?php

declare(strict_types=1);

namespace Infrastructure\Events\Listeners;

use Domain\Contact\Events\ContactScoreProcessed;
use Illuminate\Support\Facades\Log;

/**
 * Listener que grava no log após o processamento do score.
 *
 * Reage ao evento de domínio ContactScoreProcessed e escreve uma linha
 * de log no arquivo storage/logs/contact.log com os dados relevantes.
 *
 * Este listener é registrado no EventServiceProvider mapeando o evento
 * de domínio para este listener de infraestrutura.
 */
final class LogContactScoreProcessedListener
{
    /**
     * Trata o evento de domínio.
     */
    public function handle(ContactScoreProcessed $event): void
    {
        $contact = $event->contact;

        Log::channel('contact')->info('Score processado com sucesso.', [
            'contact_id' => $contact->id(),
            'email'      => $contact->email()->value(),
            'score'      => $contact->score()->value(),
            'status'     => $contact->status()->value,
            'processed_at' => $contact->processedAt()?->format('Y-m-d H:i:s'),
        ]);
    }
}
