<?php

declare(strict_types=1);

namespace Infrastructure\Events;

use Domain\Contact\Entities\Contact;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Infrastructure\Http\Resources\ContactResource;

/**
 * Evento Laravel para broadcast via WebSocket (Laravel Reverb).
 *
 * Este evento é o wrapper de infraestrutura do evento de domínio ContactScoreProcessed.
 * Ele implementa ShouldBroadcast para que o Laravel envie automaticamente
 * a mensagem via Reverb para os clientes conectados.
 *
 * Canal: contacts.{id} (canal público por nome)
 * Evento JS: ContactScoreUpdated
 *
 * O frontend escuta este canal para atualizar a UI em tempo real
 * sem necessidade de polling.
 */
final class ContactScoreUpdatedEvent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public array $contactData;

    public function __construct(
        public readonly Contact $contact,
    ) {
        // Serializa os dados do contato para evitar problemas com objetos não serializáveis
        $this->contactData = (new ContactResource($contact))->resolve();
    }

    /**
     * Define em qual canal o evento será transmitido.
     * O canal contacts.{id} permite que o frontend escute updates de um contato específico.
     */
    public function broadcastOn(): Channel
    {
        return new Channel("contacts.{$this->contact->id()}");
    }

    /**
     * Nome do evento no lado do JavaScript.
     */
    public function broadcastAs(): string
    {
        return 'ContactScoreUpdated';
    }

    /**
     * Dados enviados para o frontend via WebSocket.
     */
    public function broadcastWith(): array
    {
        return [
            'contact' => $this->contactData,
        ];
    }
}
