<?php

declare(strict_types=1);

namespace Infrastructure\Http\Resources;

use Domain\Contact\Entities\Contact;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource para padronizar a saída JSON de um Contato.
 *
 * O Resource recebe a Entidade de Domínio e a serializa para o formato
 * de resposta da API. Isso desacopla a estrutura interna do domínio
 * do contrato público da API.
 *
 * Caso precisemos mudar a representação JSON (adicionar campos, renomear),
 * só precisamos alterar este arquivo.
 *
 * @property Contact $resource
 */
final class ContactResource extends JsonResource
{
    /**
     * Transforma o contato em um array JSON.
     */
    public function toArray(Request $request): array
    {
        /** @var Contact $contact */
        $contact = $this->resource;

        return [
            'id'           => $contact->id(),
            'name'         => $contact->name(),
            'email'        => $contact->email()->value(),
            'phone'        => $contact->phone()->formatted(),
            'score'        => $contact->score()->value(),
            'status'       => $contact->status()->value,
            'status_label' => $contact->status()->label(),
            'processed_at' => $contact->processedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
