<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Configuração do Pest PHP
|--------------------------------------------------------------------------
|
| Define os datasets, helpers e comportamentos globais do Pest para
| toda a suíte de testes. Os testes de Feature usam o TestCase do Laravel;
| os Unitários usam o PHPUnit\Framework\TestCase puro.
|
*/

uses(Tests\TestCase::class)->in('Feature');
