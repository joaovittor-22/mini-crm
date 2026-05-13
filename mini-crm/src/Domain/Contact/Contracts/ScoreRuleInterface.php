<?php

declare(strict_types=1);

namespace Domain\Contact\Contracts;

use Domain\Contact\Entities\Contact;

/**
 * Interface da Estratégia de Cálculo de Score.
 *
 * Cada regra de negócio do score é implementada como uma Strategy separada.
 * Isso segue o princípio Aberto/Fechado (OCP do SOLID): novas regras podem ser
 * adicionadas sem modificar o código existente, apenas criando uma nova Strategy.
 *
 * O Domain Service ScoreCalculator aplica todas as strategies em sequência.
 */
interface ScoreRuleInterface
{
    /**
     * Calcula e retorna os pontos que esta regra contribui para o score do contato.
     *
     * @param Contact $contact O contato sendo avaliado.
     * @return int             Os pontos a serem somados (zero se a regra não se aplicar).
     */
    public function calculate(Contact $contact): int;

    /**
     * Retorna o nome descritivo desta regra (útil para debug e logs).
     */
    public function name(): string;
}
