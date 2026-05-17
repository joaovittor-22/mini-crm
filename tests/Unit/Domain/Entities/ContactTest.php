<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use Domain\Contact\Entities\Contact;
use Domain\Contact\Enums\ContactStatus;
use Domain\Contact\Exceptions\ContactCannotBeProcessedException;
use Domain\Contact\ValueObjects\Email;
use Domain\Contact\ValueObjects\Phone;
use Domain\Contact\ValueObjects\Score;
use PHPUnit\Framework\TestCase;

/**
 * Testes unitários da Entidade de Domínio Contact.
 *
 * Valida as invariantes e comportamentos da entidade:
 * - Transições de status válidas e inválidas.
 * - Aplicação de score.
 * - Métodos auxiliares (hasFullName, firstName).
 */
final class ContactTest extends TestCase
{
    private function makeContact(
        string $name = 'Joao Silva',
        string $email = 'joao@empresa.com',
        string $phone = '11999998888',
        ContactStatus $status = ContactStatus::Pending,
    ): Contact {
        return Contact::reconstitute(
            id: 1,
            name: $name,
            email: Email::fromString($email),
            phone: Phone::fromString($phone),
            score: Score::zero(),
            status: $status,
            processedAt: null,
        );
    }

    // --- Criação ---

    public function test_cria_contato_com_status_pending_e_score_zero(): void
    {
        $contact = Contact::create(
            id: 0,
            name: 'Joao Silva',
            email: Email::fromString('joao@empresa.com'),
            phone: Phone::fromString('11999998888'),
        );

        $this->assertSame(ContactStatus::Pending, $contact->status());
        $this->assertSame(0, $contact->score()->value());
        $this->assertNull($contact->processedAt());
    }

    // --- Transições de Status ---

    public function test_start_processing_muda_status_para_processing(): void
    {
        $contact = $this->makeContact(status: ContactStatus::Pending);

        $contact->startProcessing();

        $this->assertSame(ContactStatus::Processing, $contact->status());
    }

    public function test_start_processing_a_partir_de_failed_e_permitido(): void
    {
        $contact = $this->makeContact(status: ContactStatus::Failed);

        $contact->startProcessing();

        $this->assertSame(ContactStatus::Processing, $contact->status());
    }

    public function test_start_processing_a_partir_de_processing_lanca_excecao(): void
    {
        $contact = $this->makeContact(status: ContactStatus::Processing);

        $this->expectException(ContactCannotBeProcessedException::class);

        $contact->startProcessing();
    }

    public function test_start_processing_a_partir_de_active_lanca_excecao(): void
    {
        $contact = $this->makeContact(status: ContactStatus::Active);

        $this->expectException(ContactCannotBeProcessedException::class);

        $contact->startProcessing();
    }

    // --- Aplicação de Score ---

    public function test_apply_score_define_score_e_muda_status_para_active(): void
    {
        $contact = $this->makeContact(status: ContactStatus::Processing);
        $score   = Score::fromInt(50);

        $contact->applyScore($score);

        $this->assertSame(ContactStatus::Active, $contact->status());
        $this->assertSame(50, $contact->score()->value());
        $this->assertNotNull($contact->processedAt());
    }

    // --- Falha ---

    public function test_mark_as_failed_muda_status_para_failed(): void
    {
        $contact = $this->makeContact(status: ContactStatus::Processing);

        $contact->markAsFailed();

        $this->assertSame(ContactStatus::Failed, $contact->status());
    }

    // --- Métodos Auxiliares ---

    public function test_has_full_name_retorna_true_para_nome_composto(): void
    {
        $contact = $this->makeContact(name: 'Joao da Silva Pereira');

        $this->assertTrue($contact->hasFullName());
    }

    public function test_has_full_name_retorna_false_para_nome_simples(): void
    {
        $contact = $this->makeContact(name: 'Joao');

        $this->assertFalse($contact->hasFullName());
    }

    public function test_first_name_retorna_apenas_o_primeiro_nome(): void
    {
        $contact = $this->makeContact(name: 'Joao da Silva');

        $this->assertSame('Joao', $contact->firstName());
    }
}
