<?php

declare(strict_types=1);

namespace Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validação HTTP da criação de contato.
 *
 * A validação aqui é de responsabilidade da camada HTTP (Infraestrutura).
 * Valida formato, obrigatoriedade e unicidade a nível de banco.
 * Regras de domínio mais complexas ficam nos Value Objects.
 */
final class CreateContactRequest extends FormRequest
{
    /**
     * Todos os usuários autenticados (ou não, nesta API pública) podem criar contatos.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação.
     */
    public function rules(): array
    {
        return [
            // O nome deve ser uma string de até 255 caracteres
            'name'  => ['required', 'string', 'max:255'],

            // O e-mail deve ser único na tabela contacts (excluindo soft-deleted)
            'email' => ['required', 'email:rfc', 'max:255', 'unique:contacts,email'],

            // O telefone deve ter entre 10 e 15 caracteres (dígitos + formatação)
            'phone' => ['required', 'string', 'min:10', 'max:20'],
        ];
    }

    /**
     * Mensagens de validação em português.
     */
    public function messages(): array
    {
        return [
            'name.required'  => 'O campo nome é obrigatório.',
            'name.max'       => 'O nome não pode ter mais de 255 caracteres.',
            'email.required' => 'O campo e-mail é obrigatório.',
            'email.email'    => 'O e-mail informado não possui formato válido.',
            'email.unique'   => 'Este e-mail já está cadastrado.',
            'phone.required' => 'O campo telefone é obrigatório.',
            'phone.min'      => 'O telefone deve ter pelo menos 10 caracteres.',
            'phone.max'      => 'O telefone não pode ter mais de 20 caracteres.',
        ];
    }
}
