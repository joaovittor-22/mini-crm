<?php

declare(strict_types=1);

namespace Domain\Contact\Services;

use Domain\Contact\Contracts\ScoreRuleInterface;
use Domain\Contact\Entities\Contact;
use Domain\Contact\ValueObjects\Score;

/**
 * Domain Service responsável por calcular o score de um contato.
 *
 * Este serviço orquestra a execução de todas as ScoreRules (Strategy Pattern).
 * Ele não conhece os detalhes de nenhuma regra individualmente — apenas as executa
 * em sequência e agrega o resultado.
 *
 * A lista de regras é injetada via construtor (Injeção de Dependência), permitindo
 * que o Service Container do Laravel registre as regras ativas. Para adicionar
 * uma nova regra de score, basta implementar ScoreRuleInterface e registrá-la
 * no AppServiceProvider, sem modificar esta classe.
 *
 * Isso segue o OCP (Open/Closed Principle): aberto para extensão, fechado para modificação.
 */
class ScoreCalculatorService
{
    /**
     * @param ScoreRuleInterface[] $rules Lista de regras a serem aplicadas.
     */
    public function __construct(
        private readonly array $rules,
    ) {}

    /**
     * Executa todas as regras de score para o contato e retorna o score total.
     *
     * @param Contact $contact O contato a ser avaliado.
     * @return Score           O score calculado como Value Object.
     */
    public function calculate(Contact $contact): Score
    {
        $totalPoints = 0;

        foreach ($this->rules as $rule) {
            $points = $rule->calculate($contact);
            $totalPoints += $points;
        }

        return Score::fromInt($totalPoints);
    }

    /**
     * Retorna os nomes de todas as regras registradas (útil para debug).
     *
     * @return string[]
     */
    public function registeredRuleNames(): array
    {
        return array_map(
            fn (ScoreRuleInterface $rule) => $rule->name(),
            $this->rules
        );
    }
}
