<?php

declare(strict_types=1);

namespace Domain\Contact\Entities;

use DateTimeImmutable;
use Domain\Contact\Enums\ContactStatus;
use Domain\Contact\Exceptions\ContactCannotBeProcessedException;
use Domain\Contact\ValueObjects\Email;
use Domain\Contact\ValueObjects\Phone;
use Domain\Contact\ValueObjects\Score;

/**
 * Entidade rica do domínio Contact.
 *
 * Esta entidade encapsula todas as regras de negócio relacionadas
 * ao ciclo de vida de um contato, como transições de status e
 * aplicação do score calculado.
 *
 * Ela é agnóstica ao framework: sem dependência de Eloquent, HTTP ou qualquer
 * detalhe de infraestrutura. Pode ser testada de forma completamente isolada.
 *
 * Invariantes mantidas:
 * - Um contato só pode ser processado se estiver em estado 'pending' ou 'failed'.
 * - O score nunca pode ser negativo.
 * - O processed_at só é preenchido após a conclusão do processamento.
 */
final class Contact
{
    private function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly Email $email,
        private readonly Phone $phone,
        private Score $score,
        private ContactStatus $status,
        private ?DateTimeImmutable $processedAt,
    ) {}

    /**
     * Reconstitui a entidade a partir de dados persistidos (ex: banco de dados).
     * Utilizada pelos repositórios ao hidratar objetos de domínio.
     */
    public static function reconstitute(
        int $id,
        string $name,
        Email $email,
        Phone $phone,
        Score $score,
        ContactStatus $status,
        ?DateTimeImmutable $processedAt,
    ): self {
        return new self(
            id: $id,
            name: $name,
            email: $email,
            phone: $phone,
            score: $score,
            status: $status,
            processedAt: $processedAt,
        );
    }

    /**
     * Cria um novo contato com valores padrão (pending, score zero).
     * Utilizado pelo Use Case de criação.
     */
    public static function create(
        int $id,
        string $name,
        Email $email,
        Phone $phone,
    ): self {
        return new self(
            id: $id,
            name: $name,
            email: $email,
            phone: $phone,
            score: Score::zero(),
            status: ContactStatus::Pending,
            processedAt: null,
        );
    }

    // --- Getters ---

    public function id(): int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function phone(): Phone
    {
        return $this->phone;
    }

    public function score(): Score
    {
        return $this->score;
    }

    public function status(): ContactStatus
    {
        return $this->status;
    }

    public function processedAt(): ?DateTimeImmutable
    {
        return $this->processedAt;
    }

    // --- Comportamentos de Domínio (Regras de Negócio) ---

    /**
     * Marca o início do processamento do score.
     * Transição de status: pending/failed -> processing.
     *
     * @throws ContactCannotBeProcessedException se o contato não puder ser processado.
     */
    public function startProcessing(): void
    {
        if (! $this->status->canBeProcessed()) {
            throw new ContactCannotBeProcessedException(
                "O contato #{$this->id} não pode ser processado pois está no status '{$this->status->value}'."
            );
        }

        $this->status = ContactStatus::Processing;
    }

    /**
     * Aplica o score calculado e marca o contato como ativo.
     * Transição: processing -> active.
     */
    public function applyScore(Score $score): void
    {
        $this->score       = $score;
        $this->status      = ContactStatus::Active;
        $this->processedAt = new DateTimeImmutable();
    }

    /**
     * Marca o contato como falho após um erro no processamento.
     * Transição: processing -> failed.
     */
    public function markAsFailed(): void
    {
        $this->status = ContactStatus::Failed;
    }

    /**
     * Retorna o primeiro nome do contato (usado em logs e notificações).
     */
    public function firstName(): string
    {
        return explode(' ', trim($this->name))[0];
    }

    /**
     * Verifica se o nome do contato é composto (mais de uma palavra).
     * Utilizado na regra de pontuação de score.
     */
    public function hasFullName(): bool
    {
        $parts = array_filter(explode(' ', trim($this->name)));

        return count($parts) > 1;
    }
}
