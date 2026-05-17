<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use Domain\Contact\Exceptions\InvalidEmailException;
use Domain\Contact\ValueObjects\Email;
use PHPUnit\Framework\TestCase;

/**
 * Testes unitários do Value Object Email.
 *
 * Estes testes validam as regras de domínio encapsuladas no VO,
 * sem nenhuma dependência de banco de dados ou framework.
 * Ciclo TDD aplicado: Red -> Green -> Refactor.
 */
final class EmailTest extends TestCase
{
    // --- Testes de Construção / Validação ---

    public function test_aceita_email_valido(): void
    {
        $email = new Email('usuario@empresa.com.br');

        $this->assertSame('usuario@empresa.com.br', $email->value());
    }

    public function test_normaliza_email_para_lowercase(): void
    {
        $email = new Email('USUARIO@Empresa.COM');

        $this->assertSame('usuario@empresa.com', $email->value());
    }

    public function test_remove_espacos_ao_redor_do_email(): void
    {
        $email = new Email('  usuario@empresa.com  ');

        $this->assertSame('usuario@empresa.com', $email->value());
    }

    public function test_lanca_excecao_para_email_invalido(): void
    {
        $this->expectException(InvalidEmailException::class);

        new Email('nao-e-um-email');
    }

    public function test_lanca_excecao_para_email_sem_dominio(): void
    {
        $this->expectException(InvalidEmailException::class);

        new Email('usuario@');
    }

    // --- Testes de Extração de Domínio ---

    public function test_extrai_dominio_corretamente(): void
    {
        $email = new Email('usuario@minha-empresa.com.br');

        $this->assertSame('minha-empresa.com.br', $email->domain());
    }

    // --- Testes de Regras de Score ---

    public function test_identifica_email_corporativo(): void
    {
        $email = new Email('funcionario@empresa.com.br');

        $this->assertTrue($email->isCorporate());
    }

    public function test_identifica_email_gmail_como_nao_corporativo(): void
    {
        $email = new Email('usuario@gmail.com');

        $this->assertFalse($email->isCorporate());
    }

    public function test_identifica_email_hotmail_como_nao_corporativo(): void
    {
        $email = new Email('usuario@hotmail.com');

        $this->assertFalse($email->isCorporate());
    }

    public function test_identifica_email_yahoo_como_nao_corporativo(): void
    {
        $email = new Email('usuario@yahoo.com');

        $this->assertFalse($email->isCorporate());
    }

    public function test_identifica_dominio_brasileiro(): void
    {
        $email = new Email('usuario@empresa.com.br');

        $this->assertTrue($email->hasBrazilianDomain());
    }

    public function test_identifica_dominio_nao_brasileiro(): void
    {
        $email = new Email('usuario@empresa.com');

        $this->assertFalse($email->hasBrazilianDomain());
    }

    // --- Testes de Igualdade ---

    public function test_dois_emails_iguais_sao_iguais(): void
    {
        $email1 = new Email('usuario@empresa.com');
        $email2 = new Email('USUARIO@EMPRESA.COM'); // normalizado para lowercase

        $this->assertTrue($email1->equals($email2));
    }

    public function test_dois_emails_diferentes_nao_sao_iguais(): void
    {
        $email1 = new Email('a@empresa.com');
        $email2 = new Email('b@empresa.com');

        $this->assertFalse($email1->equals($email2));
    }

    // --- Testes de Serialização ---

    public function test_to_string_retorna_o_valor(): void
    {
        $email = new Email('usuario@empresa.com');

        $this->assertSame('usuario@empresa.com', (string) $email);
    }
}
