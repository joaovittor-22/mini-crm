<?php

declare(strict_types=1);

namespace Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validação HTTP da atualização de contato.
 *
 * A regra de unicidade do e-mail ignora o ID do contato atual,
 * permitindo que o contato mantenha seu próprio e-mail.
 */
final class UpdateContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Obtém o ID do contato sendo atualizado (parâmetro da rota)
        $contactId = (int) $this->route('id');

        return [
            'name'  => ['required', 'string', 'max:255'],

            // Ignora o próprio e-mail do contato na verificação de unicidade
            'email' => ['required', 'email:rfc', 'max:255', "unique:contacts,email,{$contactId}"],

            'phone' => ['required', 'string', 'min:10', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'O campo nome é obrigatório.',
            'name.max'       => 'O nome não pode ter mais de 255 caracteres.',
            'email.required' => 'O campo e-mail é obrigatório.',
            'email.email'    => 'O e-mail informado não possui formato válido.',
            'email.unique'   => 'Este e-mail já está cadastrado por outro contato.',
            'phone.required' => 'O campo telefone é obrigatório.',
            'phone.min'      => 'O telefone deve ter pelo menos 10 caracteres.',
            'phone.max'      => 'O telefone não pode ter mais de 20 caracteres.',
        ];
    }
}
