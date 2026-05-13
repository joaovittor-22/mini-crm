<?php

declare(strict_types=1);

namespace Domain\Contact\Services\ScoreRules;

use Domain\Contact\Contracts\ScoreRuleInterface;
use Domain\Contact\Entities\Contact;

/**
 * Regra de Score baseada no nome do contato.
 *
 * Pontuação:
 * - Nome composto (mais de uma palavra): +10 pontos
 *
 * A verificação de nome composto já é responsabilidade da Entidade Contact,
 * que expõe o método hasFullName(). Esta rule apenas consulta a entidade.
 */
final class NameScoreRule implements ScoreRuleInterface
{
    private const FULL_NAME_BONUS = 10;

    public function calculate(Contact $contact): int
    {
        if ($contact->hasFullName()) {
            return self::FULL_NAME_BONUS;
        }

        return 0;
    }

    public function name(): string
    {
        return 'Regra de Nome';
    }
}
