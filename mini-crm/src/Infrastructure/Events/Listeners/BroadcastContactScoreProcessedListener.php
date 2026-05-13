<?php

declare(strict_types=1);

namespace Infrastructure\Events\Listeners;

use Domain\Contact\Events\ContactScoreProcessed;
use Infrastructure\Events\ContactScoreUpdatedEvent;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Listener que dispara o broadcast WebSocket após o processamento do score.
 *
 * Reage ao evento de domínio ContactScoreProcessed e dispara o evento
 * de infraestrutura ContactScoreUpdatedEvent, que será transmitido
 * via Laravel Reverb para todos os clientes conectados ao canal
 * contacts.{id}.
 *
 * A separação entre evento de domínio e evento de broadcast é intencional:
 * o domínio não conhece e nunca conhecerá o mecanismo de WebSocket.
 */
final class BroadcastContactScoreProcessedListener
{
    public function __construct(
        private readonly Dispatcher $events,
    ) {}

    public function handle(ContactScoreProcessed $event): void
    {
        $this->events->dispatch(new ContactScoreUpdatedEvent($event->contact));
    }
}
