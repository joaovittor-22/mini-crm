<?php

declare(strict_types=1);

namespace Domain\Contact\Enums;

/**
 * Representa os possíveis estados do ciclo de vida de um contato.
 *
 * Este enum é utilizado tanto na entidade de domínio quanto no modelo Eloquent,
 * garantindo que os estados sejam sempre válidos e explícitos.
 *
 * Transições válidas:
 *   pending -> processing -> active
 *   pending -> processing -> failed
 */
enum ContactStatus: string
{
    case Pending    = 'pending';
    case Processing = 'processing';
    case Active     = 'active';
    case Failed     = 'failed';

    /**
     * Verifica se o contato já foi processado (terminal bem-sucedido).
     */
    public function isProcessed(): bool
    {
        return $this === self::Active;
    }

    /**
     * Verifica se o contato ainda pode iniciar um processamento de score.
     * Apenas contatos em estado 'pending' ou 'failed' podem ser re-processados.
     */
    public function canBeProcessed(): bool
    {
        return in_array($this, [self::Pending, self::Failed], strict: true);
    }

    /**
     * Retorna o label legível para humanos do status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending    => 'Pendente',
            self::Processing => 'Processando',
            self::Active     => 'Ativo',
            self::Failed     => 'Falhou',
        };
    }
}
