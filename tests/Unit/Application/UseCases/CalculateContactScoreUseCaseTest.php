<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases;

use Application\Contact\UseCases\CalculateContactScoreUseCase;
use Domain\Contact\Contracts\ContactRepositoryInterface;
use Domain\Contact\Entities\Contact;
use Domain\Contact\Enums\ContactStatus;
use Domain\Contact\Events\ContactScoreProcessed;
use Domain\Contact\Services\ScoreCalculatorService;
use Domain\Contact\ValueObjects\Email;
use Domain\Contact\ValueObjects\Phone;
use Domain\Contact\ValueObjects\Score;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

/**
 * Testes unitários do Use Case CalculateContactScoreUseCase.
 *
 * Utiliza mocks para isolar completamente o Use Case da infraestrutura.
 * Testa o fluxo completo de processamento, incluindo tratamento de falhas.
 *
 * Nota: o sleep(2) é removido para testes — o Use Case real contém o sleep
 * apenas para emular latência em ambiente não-test.
 */
final class CalculateContactScoreUseCaseTest extends TestCase
{
    private MockInterface $repository;
    private MockInterface $calculator;
    private MockInterface $dispatcher;
    private CalculateContactScoreUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(ContactRepositoryInterface::class);
        $this->calculator = Mockery::mock(ScoreCalculatorService::class);
        $this->dispatcher = Mockery::mock(Dispatcher::class);

        $this->useCase = new CalculateContactScoreUseCase(
            repository: $this->repository,
            calculator: $this->calculator,
            events: $this->dispatcher,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Cria uma entidade Contact para uso nos testes.
     */
    private function makeContact(ContactStatus $status = ContactStatus::Pending): Contact
    {
        return Contact::reconstitute(
            id: 1,
            name: 'Joao Silva',
            email: Email::fromString('joao@empresa.com.br'),
            phone: Phone::fromString('11999998888'),
            score: Score::zero(),
            status: $status,
            processedAt: null,
        );
    }

    public function test_processa_score_com_sucesso(): void
    {
        $contact = $this->makeContact();
        $score   = Score::fromInt(60);

        // O repositório deve retornar o contato ao ser consultado
        $this->repository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($contact);

        // O repositório deve ser chamado para salvar (2x: processing + active)
        $this->repository
            ->shouldReceive('save')
            ->twice()
            ->andReturnUsing(fn (Contact $c) => $c);

        // O calculador deve retornar o score
        $this->calculator
            ->shouldReceive('calculate')
            ->once()
            ->with($contact)
            ->andReturn($score);

        // O evento de domínio deve ser disparado
        $this->dispatcher
            ->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::type(ContactScoreProcessed::class));

        $result = $this->useCase->execute(1);

        $this->assertSame(ContactStatus::Active, $result->status());
        $this->assertSame(60, $result->score()->value());
        $this->assertNotNull($result->processedAt());
    }

    public function test_marca_contato_como_failed_em_caso_de_excecao(): void
    {
        $contact = $this->makeContact();

        $this->repository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($contact);

        // Primeira chamada ao save: status processing
        // Terceira chamada ao save: status failed
        $this->repository
            ->shouldReceive('save')
            ->times(2)
            ->andReturnUsing(fn (Contact $c) => $c);

        // O calculador lança uma exceção
        $this->calculator
            ->shouldReceive('calculate')
            ->once()
            ->andThrow(new \RuntimeException('Erro simulado no cálculo'));

        // O evento NÃO deve ser disparado em caso de falha
        $this->dispatcher->shouldNotReceive('dispatch');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Erro simulado no cálculo');

        $this->useCase->execute(1);
    }
}
