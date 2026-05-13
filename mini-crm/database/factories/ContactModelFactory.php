<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Contact\Enums\ContactStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Infrastructure\Persistence\Eloquent\Models\ContactModel;

/**
 * Factory do Modelo Eloquent ContactModel.
 *
 * Utilizada nos testes para criar contatos de forma rápida e configurável.
 * Os estados (states) permitem criar contatos em cenários específicos.
 */
final class ContactModelFactory extends Factory
{
    protected $model = ContactModel::class;

    /**
     * Define os atributos padrão de um contato de teste.
     * O telefone é gerado sem formatação para simular o armazenamento normalizado.
     */
    public function definition(): array
    {
        // Gera um DDD aleatório de SP ou outro estado para variar os testes
        $ddd   = $this->faker->randomElement(['11', '12', '21', '31', '41', '51', '71', '85']);
        $phone = $ddd . $this->faker->numerify('9########');

        return [
            'name'         => $this->faker->name(),
            'email'        => $this->faker->unique()->safeEmail(),
            'phone'        => $phone,
            'score'        => 0,
            'status'       => ContactStatus::Pending->value,
            'processed_at' => null,
        ];
    }

    /**
     * Estado: contato totalmente processado com score alto.
     */
    public function processed(): static
    {
        return $this->state(fn () => [
            'score'        => $this->faker->numberBetween(10, 60),
            'status'       => ContactStatus::Active->value,
            'processed_at' => now(),
        ]);
    }

    /**
     * Estado: contato em processamento.
     */
    public function processing(): static
    {
        return $this->state(fn () => [
            'status' => ContactStatus::Processing->value,
        ]);
    }

    /**
     * Estado: contato com processamento falho.
     */
    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => ContactStatus::Failed->value,
        ]);
    }

    /**
     * Estado: contato com e-mail corporativo .br (score máximo possível).
     */
    public function corporate(): static
    {
        return $this->state(fn () => [
            'name'  => $this->faker->firstName() . ' ' . $this->faker->lastName(),
            'email' => $this->faker->userName() . '@empresa.com.br',
            'phone' => '11' . $this->faker->numerify('9########'),
        ]);
    }
}
