<?php

declare(strict_types=1);

namespace Infrastructure\Providers;

use Domain\Contact\Events\ContactScoreProcessed;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Infrastructure\Events\Listeners\BroadcastContactScoreProcessedListener;
use Infrastructure\Events\Listeners\LogContactScoreProcessedListener;

/**
 * Service Provider de Eventos.
 *
 * Mapeia eventos (de domínio ou de infraestrutura) aos seus Listeners.
 *
 * O evento de domínio ContactScoreProcessed tem dois listeners:
 * 1. LogContactScoreProcessedListener    - grava no log storage/logs/contact.log
 * 2. BroadcastContactScoreProcessedListener - dispara broadcast via Reverb
 *
 * A ordem dos listeners importa: o log é gravado antes do broadcast.
 */
final class EventServiceProvider extends ServiceProvider
{
    /**
     * Mapa de Eventos -> Listeners.
     *
     * @var array<string, array<string>>
     */
    protected $listen = [
        ContactScoreProcessed::class => [
            LogContactScoreProcessedListener::class,
            BroadcastContactScoreProcessedListener::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
