<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use Domain\Contact\Exceptions\InvalidScoreException;
use Domain\Contact\ValueObjects\Score;
use PHPUnit\Framework\TestCase;

/**
 * Testes unitários do Value Object Score.
 */
final class ScoreTest extends TestCase
{
    public function test_cria_score_com_zero(): void
    {
        $score = Score::zero();

        $this->assertSame(0, $score->value());
    }

    public function test_cria_score_com_valor_positivo(): void
    {
        $score = Score::fromInt(60);

        $this->assertSame(60, $score->value());
    }

    public function test_lanca_excecao_para_score_negativo(): void
    {
        $this->expectException(InvalidScoreException::class);

        Score::fromInt(-1);
    }

    public function test_add_retorna_nova_instancia_com_valor_somado(): void
    {
        $score    = Score::fromInt(20);
        $novo     = $score->add(10);

        // Imutabilidade: o original não foi alterado
        $this->assertSame(20, $score->value());
        $this->assertSame(30, $novo->value());
        $this->assertNotSame($score, $novo);
    }

    public function test_dois_scores_iguais_sao_iguais(): void
    {
        $a = Score::fromInt(50);
        $b = Score::fromInt(50);

        $this->assertTrue($a->equals($b));
    }

    public function test_dois_scores_diferentes_nao_sao_iguais(): void
    {
        $a = Score::fromInt(20);
        $b = Score::fromInt(30);

        $this->assertFalse($a->equals($b));
    }

    public function test_to_string_retorna_valor_como_string(): void
    {
        $score = Score::fromInt(45);

        $this->assertSame('45', (string) $score);
    }
}
