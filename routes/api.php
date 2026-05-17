<?php

use Illuminate\Support\Facades\Route;
use Infrastructure\Http\Controllers\ContactController;

/*
|--------------------------------------------------------------------------
| Rotas da API - Mini CRM de Contatos
|--------------------------------------------------------------------------
|
| Todas as rotas estão prefixadas com /api pelo bootstrap da aplicação.
| Não há autenticação nesta versão do desafio técnico.
|
*/

// Grupo de rotas de contatos
Route::prefix('contacts')->group(function () {

    // CRUD padrão
    Route::get('/', [ContactController::class, 'index']);
    Route::post('/', [ContactController::class, 'store']);
    Route::get('/{id}', [ContactController::class, 'show'])->whereNumber('id');
    Route::put('/{id}', [ContactController::class, 'update'])->whereNumber('id');
    Route::delete('/{id}', [ContactController::class, 'destroy'])->whereNumber('id');

    // Endpoint de gatilho do processamento assíncrono de score
    Route::post('/{id}/process-score', [ContactController::class, 'processScore'])->whereNumber('id');
});
