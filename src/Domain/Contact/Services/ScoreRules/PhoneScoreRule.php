<?php

declare(strict_types=1);

namespace Domain\Contact\Services\ScoreRules;

use Domain\Contact\Contracts\ScoreRuleInterface;
use Domain\Contact\Entities\Contact;

/**
 * Regra de Score baseada no telefone do contato.
 *
 * Pontuação:
 * - DDD de São Paulo (11 a 19): +20 pontos
 * - DDD de outro estado brasileiro (qualquer DDD válido): +10 pontos
 *
 * As condições são mutuamente exclusivas: apenas uma delas é aplicada por vez.
 */
final class PhoneScoreRule implements ScoreRuleInterface
{
    private const SAO_PAULO_BONUS = 20;
    private const OTHER_STATE_BONUS = 10;

    public function calculate(Contact $contact): int
    {
        $phone = $contact->phone();

        if ($phone->hasSaoPauloAreaCode()) {
            return self::SAO_PAULO_BONUS;
        }

        if ($phone->hasValidAreaCode()) {
            return self::OTHER_STATE_BONUS;
        }

        return 0;
    }

    public function name(): string
    {
        return 'Regra de Telefone';
    }
}
