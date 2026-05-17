<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use Domain\Contact\Exceptions\InvalidPhoneException;
use Domain\Contact\ValueObjects\Phone;
use PHPUnit\Framework\TestCase;

/**
 * Testes unitários do Value Object Phone.
 */
final class PhoneTest extends TestCase
{
    // --- Testes de Construção ---

    public function test_aceita_telefone_fixo_com_10_digitos(): void
    {
        $phone = new Phone('1133334444');

        $this->assertSame('1133334444', $phone->value());
    }

    public function test_aceita_celular_com_11_digitos(): void
    {
        $phone = new Phone('11999998888');

        $this->assertSame('11999998888', $phone->value());
    }

    public function test_remove_formatacao_e_normaliza(): void
    {
        $phone = new Phone('(11) 99999-8888');

        $this->assertSame('11999998888', $phone->value());
    }

    public function test_remove_codigo_pais_55(): void
    {
        $phone = new Phone('5511999998888');

        $this->assertSame('11999998888', $phone->value());
    }

    public function test_lanca_excecao_para_telefone_com_menos_de_10_digitos(): void
    {
        $this->expectException(InvalidPhoneException::class);

        new Phone('123456789');
    }

    public function test_lanca_excecao_para_telefone_com_mais_de_11_digitos(): void
    {
        $this->expectException(InvalidPhoneException::class);

        new Phone('119999988881');
    }

    // --- Testes de Formatação ---

    public function test_formata_celular_corretamente(): void
    {
        $phone = new Phone('11999998888');

        $this->assertSame('(11) 99999-8888', $phone->formatted());
    }

    public function test_formata_fixo_corretamente(): void
    {
        $phone = new Phone('1133334444');

        $this->assertSame('(11) 3333-4444', $phone->formatted());
    }

    // --- Testes de Regras de Score ---

    public function test_identifica_ddd_de_sao_paulo_11(): void
    {
        $phone = new Phone('11999998888');

        $this->assertTrue($phone->hasSaoPauloAreaCode());
    }

    public function test_identifica_ddd_de_sao_paulo_19(): void
    {
        $phone = new Phone('19999998888');

        $this->assertTrue($phone->hasSaoPauloAreaCode());
    }

    public function test_identifica_ddd_de_outro_estado(): void
    {
        $phone = new Phone('21999998888'); // Rio de Janeiro

        $this->assertFalse($phone->hasSaoPauloAreaCode());
        $this->assertTrue($phone->hasValidAreaCode());
    }

    public function test_todos_ddds_de_sp_sao_reconhecidos(): void
    {
        // DDDs de SP: 11, 12, 13, 14, 15, 16, 17, 18, 19
        foreach (range(11, 19) as $ddd) {
            $phone = new Phone("{$ddd}999998888");
            $this->assertTrue(
                $phone->hasSaoPauloAreaCode(),
                "DDD {$ddd} deveria ser reconhecido como SP."
            );
        }
    }

    // --- Testes de Extração ---

    public function test_extrai_ddd_corretamente(): void
    {
        $phone = new Phone('21999998888');

        $this->assertSame(21, $phone->areaCode());
    }
}
