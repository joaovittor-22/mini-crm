<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use Domain\Contact\Enums\ContactStatus;
use Domain\Contact\Events\ContactScoreProcessed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Infrastructure\Persistence\Eloquent\Models\ContactModel;
use Infrastructure\Queue\Jobs\ProcessContactScoreJob;
use Tests\TestCase;

/**
 * Testes de Feature para o fluxo completo de processamento de score.
 *
 * Cobre o ciclo de vida completo:
 * - Disparo do Job via endpoint.
 * - Execução do Job com fila sync (em testes).
 * - Disparo do evento de domínio.
 * - Persistência do score no banco.
 */
final class ProcessScoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_endpoint_enfileira_job_de_score(): void
    {
        Queue::fake();

        $model = ContactModel::factory()->create(['status' => ContactStatus::Pending]);

        $this->postJson("/api/contacts/{$model->id}/process-score")
            ->assertAccepted();

        Queue::assertPushed(ProcessContactScoreJob::class);
    }

    public function test_job_executa_e_salva_score_no_banco(): void
    {
        // Usando fila sync (configurada no phpunit.xml), o Job executa na hora
        $model = ContactModel::factory()->corporate()->create();

        // Executa o Job diretamente para testar a integração
        (new ProcessContactScoreJob($model->id))->handle(
            app(\Application\Contact\UseCases\CalculateContactScoreUseCase::class)
        );

        $model->refresh();

        // O score deve ser maior que zero para um contato corporativo de SP
        $this->assertGreaterThan(0, $model->score);
        $this->assertSame(ContactStatus::Active->value, $model->status->value);
        $this->assertNotNull($model->processed_at);
    }

    public function test_evento_de_dominio_e_disparado_apos_processamento(): void
    {
        Event::fake([ContactScoreProcessed::class]);

        $model = ContactModel::factory()->create();

        (new ProcessContactScoreJob($model->id))->handle(
            app(\Application\Contact\UseCases\CalculateContactScoreUseCase::class)
        );

        Event::assertDispatched(ContactScoreProcessed::class, function ($event) use ($model) {
            return $event->contact->id() === $model->id;
        });
    }

    public function test_score_maximo_para_contato_corporativo_sp_nome_completo(): void
    {
        // Contato com todas as regras de score máximas
        $model = ContactModel::factory()->create([
            'name'  => 'Joao da Silva',          // +10 (nome completo)
            'email' => 'joao@empresa.com.br',     // +20 (corporativo) + +10 (.br)
            'phone' => '11999998888',              // +20 (DDD SP)
        ]);

        (new ProcessContactScoreJob($model->id))->handle(
            app(\Application\Contact\UseCases\CalculateContactScoreUseCase::class)
        );

        $model->refresh();

        // Score esperado: 60 pontos (máximo possível)
        $this->assertSame(60, $model->score);
    }

    public function test_score_minimo_para_contato_gmail_nome_simples_outro_estado(): void
    {
        $model = ContactModel::factory()->create([
            'name'  => 'Joao',              // 0 (nome simples)
            'email' => 'joao@gmail.com',    // 0 (gratuito, não .br)
            'phone' => '21999998888',        // +10 (outro estado)
        ]);

        (new ProcessContactScoreJob($model->id))->handle(
            app(\Application\Contact\UseCases\CalculateContactScoreUseCase::class)
        );

        $model->refresh();

        $this->assertSame(10, $model->score);
    }
}
