<?php

declare(strict_types=1);

namespace Database\Seeders;

use Domain\Contact\Enums\ContactStatus;
use Illuminate\Database\Seeder;
use Infrastructure\Persistence\Eloquent\Models\ContactModel;

/**
 * Seeder principal da aplicação.
 *
 * Cria contatos de exemplo cobrindo todos os cenários de pontuação,
 * o que facilita o desenvolvimento e a demonstração do sistema.
 *
 * Execute com: php artisan db:seed
 */
final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Contato com score máximo (60 pontos)
        // E-mail corporativo .br (+30), nome completo (+10), DDD SP (+20)
        ContactModel::factory()->create([
            'name'  => 'Ana Paula Ferreira',
            'email' => 'ana.paula@empresa.com.br',
            'phone' => '11999998888',
            'score' => 0,
            'status' => ContactStatus::Pending->value,
        ]);

        // Contato com score mínimo (10 pontos)
        // Gmail (0), nome simples (0), DDD RJ (+10)
        ContactModel::factory()->create([
            'name'  => 'Carlos',
            'email' => 'carlos@gmail.com',
            'phone' => '21988887777',
        ]);

        // Contato com e-mail corporativo sem .br (+20) e DDD SP (+20)
        ContactModel::factory()->create([
            'name'  => 'Beatriz Souza',
            'email' => 'beatriz@empresa.com',
            'phone' => '15999996666',
        ]);

        // Contato já processado (status active)
        ContactModel::factory()->processed()->create([
            'name'  => 'Diego Martins',
            'email' => 'diego@hotmail.com',
            'phone' => '31977776666',
        ]);

        // 10 contatos aleatórios com a factory
        ContactModel::factory()->count(10)->create();

        $this->command->info('Contatos de exemplo criados com sucesso.');
    }
}
