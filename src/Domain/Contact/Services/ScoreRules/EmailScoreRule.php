<?php

declare(strict_types=1);

namespace Domain\Contact\Services\ScoreRules;

use Domain\Contact\Contracts\ScoreRuleInterface;
use Domain\Contact\Entities\Contact;

/**
 * Regra de Score baseada no e-mail do contato.
 *
 * Pontuação:
 * - E-mail corporativo (não gmail, hotmail, yahoo, etc.): +20 pontos
 * - Domínio terminado em .br (qualquer): +10 pontos
 *
 * As duas condições são independentes e cumulativas.
 * Exemplo: contato@empresa.com.br = +20 (corporativo) + +10 (.br) = +30 pontos.
 */
final class EmailScoreRule implements ScoreRuleInterface
{
    private const CORPORATE_BONUS = 20;
    private const BRAZIL_DOMAIN_BONUS = 10;

    public function calculate(Contact $contact): int
    {
        $points = 0;
        $email  = $contact->email();

        if ($email->isCorporate()) {
            $points += self::CORPORATE_BONUS;
        }

        if ($email->hasBrazilianDomain()) {
            $points += self::BRAZIL_DOMAIN_BONUS;
        }

        return $points;
    }

    public function name(): string
    {
        return 'Regra de E-mail';
    }
}
