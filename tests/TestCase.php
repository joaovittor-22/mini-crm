<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Classe base para todos os testes da aplicação.
 *
 * Extende o TestCase do Laravel, que inicializa o container
 * de injeção de dependência, banco de dados e demais recursos.
 *
 * Os testes de Feature herdam daqui para ter acesso ao cliente HTTP.
 * Os testes unitários herdam diretamente de PHPUnit\Framework\TestCase.
 */
abstract class TestCase extends BaseTestCase
{
    // Ponto de extensão para configurações globais de teste
}
