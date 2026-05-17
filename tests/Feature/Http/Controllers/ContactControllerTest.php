<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use Domain\Contact\Enums\ContactStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Infrastructure\Persistence\Eloquent\Models\ContactModel;
use Infrastructure\Queue\Jobs\ProcessContactScoreJob;
use Tests\TestCase;

/**
 * Testes de Feature (Integração) dos endpoints de Contatos.
 *
 * Estes testes exercitam toda a stack da aplicação: HTTP -> Controller
 * -> Use Case -> Repository -> Banco de Dados.
 *
 * Usamos RefreshDatabase para garantir isolamento entre testes.
 * O banco de dados de teste é configurado no phpunit.xml.
 */
final class ContactControllerTest extends TestCase
{
    use RefreshDatabase;

    // --- POST /api/contacts ---

    public function test_cria_contato_com_sucesso(): void
    {
        $payload = [
            'name'  => 'Joao Silva',
            'email' => 'joao@empresa.com.br',
            'phone' => '(11) 99999-8888',
        ];

        $response = $this->postJson('/api/contacts', $payload);

        $response->assertCreated()
            ->assertJsonStructure([
                'id', 'name', 'email', 'phone', 'score', 'status', 'status_label', 'processed_at',
            ])
            ->assertJsonFragment([
                'name'   => 'Joao Silva',
                'email'  => 'joao@empresa.com.br',
                'status' => 'pending',
                'score'  => 0,
            ]);

        $this->assertDatabaseHas('contacts', [
            'email' => 'joao@empresa.com.br',
        ]);
    }

    public function test_falha_ao_criar_contato_com_email_duplicado(): void
    {
        ContactModel::factory()->create(['email' => 'joao@empresa.com']);

        $response = $this->postJson('/api/contacts', [
            'name'  => 'Outro Joao',
            'email' => 'joao@empresa.com',
            'phone' => '11999998888',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_falha_ao_criar_contato_sem_campos_obrigatorios(): void
    {
        $response = $this->postJson('/api/contacts', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'phone']);
    }

    public function test_normaliza_telefone_ao_criar_contato(): void
    {
        $this->postJson('/api/contacts', [
            'name'  => 'Joao Silva',
            'email' => 'joao@empresa.com',
            'phone' => '(11) 99999-8888',
        ]);

        // Observer deve normalizar o telefone para somente dígitos
        $this->assertDatabaseHas('contacts', [
            'phone' => '11999998888',
        ]);
    }

    // --- GET /api/contacts ---

    public function test_lista_contatos_com_paginacao(): void
    {
        ContactModel::factory()->count(20)->create();

        $response = $this->getJson('/api/contacts?per_page=10&page=1');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);

        $this->assertCount(10, $response->json('data'));
        $this->assertSame(20, $response->json('meta.total'));
    }

    public function test_lista_vazia_quando_nao_ha_contatos(): void
    {
        $response = $this->getJson('/api/contacts');

        $response->assertOk()
            ->assertJson(['data' => []]);
    }

    // --- GET /api/contacts/{id} ---

    public function test_exibe_contato_existente(): void
    {
        $model = ContactModel::factory()->create();

        $response = $this->getJson("/api/contacts/{$model->id}");

        $response->assertOk()
            ->assertJsonFragment(['id' => $model->id]);
    }

    public function test_retorna_404_para_contato_inexistente(): void
    {
        $response = $this->getJson('/api/contacts/99999');

        $response->assertNotFound();
    }

    // --- PUT /api/contacts/{id} ---

    public function test_atualiza_contato_com_sucesso(): void
    {
        $model = ContactModel::factory()->create();

        $response = $this->putJson("/api/contacts/{$model->id}", [
            'name'  => 'Nome Atualizado',
            'email' => 'novo@empresa.com',
            'phone' => '21988887777',
        ]);

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Nome Atualizado']);

        $this->assertDatabaseHas('contacts', [
            'id'    => $model->id,
            'email' => 'novo@empresa.com',
        ]);
    }

    public function test_preserva_score_e_status_ao_atualizar_contato(): void
    {
        $model = ContactModel::factory()->create([
            'score'  => 50,
            'status' => ContactStatus::Active,
        ]);

        $this->putJson("/api/contacts/{$model->id}", [
            'name'  => 'Nome Novo',
            'email' => 'novo@empresa.com',
            'phone' => '21988887777',
        ]);

        // Score e status NÃO devem ser alterados pela atualização de dados
        $this->assertDatabaseHas('contacts', [
            'id'     => $model->id,
            'score'  => 50,
            'status' => 'active',
        ]);
    }

    // --- DELETE /api/contacts/{id} ---

    public function test_exclui_contato_com_soft_delete(): void
    {
        $model = ContactModel::factory()->create();

        $response = $this->deleteJson("/api/contacts/{$model->id}");

        $response->assertNoContent();

        // O registro deve existir no banco (soft delete)
        $this->assertSoftDeleted('contacts', ['id' => $model->id]);
    }

    public function test_retorna_404_ao_excluir_contato_inexistente(): void
    {
        $response = $this->deleteJson('/api/contacts/99999');

        $response->assertNotFound();
    }

    // --- POST /api/contacts/{id}/process-score ---

    public function test_enfileira_processamento_de_score(): void
    {
        Queue::fake();

        $model = ContactModel::factory()->create(['status' => ContactStatus::Pending]);

        $response = $this->postJson("/api/contacts/{$model->id}/process-score");

        $response->assertAccepted()
            ->assertJsonFragment(['message' => 'O processamento do score foi enfileirado com sucesso.']);

        Queue::assertPushed(ProcessContactScoreJob::class, function ($job) use ($model) {
            return true; // Valida apenas que o Job foi enfileirado
        });
    }

    public function test_retorna_404_ao_processar_score_de_contato_inexistente(): void
    {
        $response = $this->postJson('/api/contacts/99999/process-score');

        $response->assertNotFound();
    }
}
