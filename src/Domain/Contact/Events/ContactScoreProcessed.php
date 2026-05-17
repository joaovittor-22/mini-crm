<?php

declare(strict_types=1);

namespace Domain\Contact\Events;

use Domain\Contact\Entities\Contact;

/**
 * Evento de Domínio disparado após o processamento bem-sucedido do score de um contato.
 *
 * Eventos de domínio comunicam que algo importante aconteceu dentro do domínio.
 * Listeners (na camada de Infraestrutura) reagem a este evento para realizar
 * efeitos colaterais: gravação de log, broadcast via WebSocket, etc.
 *
 * Importante: este evento é apenas um Data Transfer Object (imutável).
 * Ele não conhece nenhum detalhe de infraestrutura.
 */
final class ContactScoreProcessed
{
    public function __construct(
        public readonly Contact $contact,
    ) {}
}
