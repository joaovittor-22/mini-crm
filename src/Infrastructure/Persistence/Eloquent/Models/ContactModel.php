<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Eloquent\Models;

use Domain\Contact\Enums\ContactStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo Eloquent do Contact — camada de infraestrutura de persistência.
 *
 * Este modelo é responsável apenas pela interação com o banco de dados.
 * Ele é completamente separado da Entidade de Domínio (Domain\Contact\Entities\Contact).
 *
 * O Repositório (EloquentContactRepository) faz o mapeamento entre este modelo
 * e a Entidade de Domínio.
 *
 * Observer: ContactModelObserver normaliza o telefone antes de salvar (evento 'saving').
 */
class ContactModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Nome da tabela no banco de dados.
     */
    protected $table = 'contacts';

    /**
     * Campos permitidos para mass assignment.
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'score',
        'status',
        'processed_at',
    ];

    /**
     * Conversão automática de tipos (casting).
     * O status é convertido automaticamente para o enum ContactStatus.
     */
    protected $casts = [
        'score'        => 'integer',
        'status'       => ContactStatus::class,
        'processed_at' => 'datetime',
    ];

    /**
     * Nome da factory para uso nos testes.
     */
    protected static function newFactory(): \Database\Factories\ContactModelFactory
    {
        return \Database\Factories\ContactModelFactory::new();
    }
}
