<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Services;

use Domain\Contact\Entities\Contact;
use Domain\Contact\Enums\ContactStatus;
use Domain\Contact\Services\ScoreCalculatorService;
use Domain\Contact\Services\ScoreRules\EmailScoreRule;
use Domain\Contact\Services\ScoreRules\NameScoreRule;
use Domain\Contact\Services\ScoreRules\PhoneScoreRule;
use Domain\Contact\ValueObjects\Email;
use Domain\Contact\ValueObjects\Phone;
use Domain\Contact\ValueObjects\Score;
use PHPUnit\Framework\TestCase;

/**
 * Testes unitários do Domain Service ScoreCalculatorService.
 *
 * Valida as regras de negócio de pontuação de forma isolada,
 * sem acesso a banco de dados ou qualquer infraestrutura.
 *
 * Cenários de pontuação testados:
 * - E-mail corporativo .br + nome completo + SP: 20 + 10 + 10 + 20 = 60
 * - E-mail gmail + nome simples + outro estado:  0  + 0  + 0  + 10 = 10
 */
final class ScoreCalculatorServiceTest extends TestCase
{
    private ScoreCalculatorService $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        // Instancia o calculador com todas as regras reais
        $this->calculator = new ScoreCalculatorService(rules: [
            new EmailScoreRule(),
            new NameScoreRule(),
            new PhoneScoreRule(),
        ]);
    }

    /**
     * Cria um contato de domínio para uso nos testes.
     */
    private function makeContact(string $name, string $email, string $phone): Contact
    {
        return Contact::reconstitute(
            id: 1,
            name: $name,
            email: Email::fromString($email),
            phone: Phone::fromString($phone),
            score: Score::zero(),
            status: ContactStatus::Pending,
            processedAt: null,
        );
    }

    // --- Regra de E-mail ---

    public function test_email_corporativo_recebe_20_pontos(): void
    {
        $contact = $this->makeContact('Joao', 'joao@empresa.com', '21999998888');

        $score = $this->calculator->calculate($contact);

        // Corporativo: +20, Outro estado: +10 = 30
        $this->assertSame(30, $score->value());
    }

    public function test_email_corporativo_br_recebe_30_pontos(): void
    {
        $contact = $this->makeContact('Joao', 'joao@empresa.com.br', '21999998888');

        $score = $this->calculator->calculate($contact);

        // Corporativo: +20, .br: +10, Outro estado: +10 = 40
        $this->assertSame(40, $score->value());
    }

    public function test_gmail_nao_recebe_pontos_de_email(): void
    {
        $contact = $this->makeContact('Joao', 'joao@gmail.com', '21999998888');

        $score = $this->calculator->calculate($contact);

        // Gmail: 0, Outro estado: +10 = 10
        $this->assertSame(10, $score->value());
    }

    // --- Regra de Nome ---

    public function test_nome_completo_recebe_10_pontos(): void
    {
        $contact = $this->makeContact('Joao Silva', 'joao@gmail.com', '21999998888');

        $score = $this->calculator->calculate($contact);

        // Nome composto: +10, Outro estado: +10 = 20
        $this->assertSame(20, $score->value());
    }

    public function test_nome_simples_nao_recebe_pontos(): void
    {
        $contact = $this->makeContact('Joao', 'joao@gmail.com', '21999998888');

        $score = $this->calculator->calculate($contact);

        // Nome simples: 0, Outro estado: +10 = 10
        $this->assertSame(10, $score->value());
    }

    // --- Regra de Telefone ---

    public function test_ddd_sp_recebe_20_pontos(): void
    {
        $contact = $this->makeContact('Joao', 'joao@gmail.com', '11999998888');

        $score = $this->calculator->calculate($contact);

        // Gmail: 0, Nome simples: 0, DDD SP: +20 = 20
        $this->assertSame(20, $score->value());
    }

    public function test_ddd_outro_estado_recebe_10_pontos(): void
    {
        $contact = $this->makeContact('Joao', 'joao@gmail.com', '21999998888');

        $score = $this->calculator->calculate($contact);

        // Gmail: 0, Nome simples: 0, DDD RJ: +10 = 10
        $this->assertSame(10, $score->value());
    }

    // --- Score Máximo ---

    public function test_contato_com_todas_as_regras_maximas_recebe_60_pontos(): void
    {
        // Corporativo .br (+30) + Nome completo (+10) + DDD SP (+20) = 60
        $contact = $this->makeContact('Joao Silva', 'joao@empresa.com.br', '11999998888');

        $score = $this->calculator->calculate($contact);

        $this->assertSame(60, $score->value());
    }

    // --- Regras Registradas ---

    public function test_retorna_nomes_das_regras_registradas(): void
    {
        $names = $this->calculator->registeredRuleNames();

        $this->assertContains('Regra de E-mail', $names);
        $this->assertContains('Regra de Nome', $names);
        $this->assertContains('Regra de Telefone', $names);
    }

    // --- Score Sem Regras ---

    public function test_calculadora_sem_regras_retorna_score_zero(): void
    {
        $calculatorSemRegras = new ScoreCalculatorService(rules: []);
        $contact = $this->makeContact('Joao Silva', 'joao@empresa.com.br', '11999998888');

        $score = $calculatorSemRegras->calculate($contact);

        $this->assertSame(0, $score->value());
    }
}
