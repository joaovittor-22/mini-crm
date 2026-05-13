<?php

use Domain\Contact\Enums\ContactStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration que cria a tabela de contatos.
 *
 * Campos notáveis:
 * - score: integer com default 0 (calculado assincronamente)
 * - status: enum mapeado para o enum de domínio ContactStatus
 * - processed_at: preenchido apenas após o processamento do score
 * - deleted_at: suporte a soft delete (o registro não é apagado do banco)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('email')->unique();

            // Armazenamos apenas dígitos (normalizado pelo Observer)
            $table->string('phone', 20);

            $table->integer('score')->default(0);

            // O enum é armazenado como string; o cast no Model converte para ContactStatus
            $table->enum('status', array_column(ContactStatus::cases(), 'value'))
                ->default(ContactStatus::Pending->value);

            $table->timestamp('processed_at')->nullable();

            // Soft delete: registros excluídos recebem este timestamp
            $table->softDeletes();

            $table->timestamps();

            // Índices para consultas frequentes
            $table->index('status');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
