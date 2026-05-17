<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Eloquent;

use Domain\Contact\ValueObjects\Phone;
use Infrastructure\Persistence\Eloquent\Models\ContactModel;
use Illuminate\Support\Facades\Log;

/**
 * Observer do Modelo Eloquent ContactModel.
 *
 * Observa eventos do ciclo de vida do Eloquent e aplica transformações
 * automáticas antes da persistência.
 *
 * Evento 'saving': disparado antes de qualquer INSERT ou UPDATE.
 * Aqui normalizamos o telefone para o formato de somente dígitos,
 * independente de como o usuário enviou (com parênteses, traços, etc.).
 *
 * Isso garante consistência no banco de dados e facilita buscas e comparações.
 */
final class ContactModelObserver
{
    /**
     * Executado antes de salvar (INSERT ou UPDATE) um ContactModel.
     * Normaliza o campo phone para conter apenas dígitos.
     */
    public function saving(ContactModel $model): void
    {
        if (! empty($model->phone)) {
            try {
                $phone = Phone::fromString($model->phone);

                // Armazena apenas os dígitos no banco
                $model->phone = $phone->value();
            } catch (\Throwable $e) {
                // Se o telefone já estiver normalizado ou inválido, registra e mantém como está
                Log::warning("ContactModelObserver: não foi possível normalizar o telefone '{$model->phone}': {$e->getMessage()}");
            }
        }
    }
}
