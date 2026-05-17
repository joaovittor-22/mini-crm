<?php

/*
|--------------------------------------------------------------------------
| Bootstrap da Aplicação Laravel 11
|--------------------------------------------------------------------------
|
| Registra os Service Providers da aplicação, incluindo os nossos da
| camada de Infraestrutura, e configura o tratamento de exceções de domínio.
|
*/

use Domain\Contact\Exceptions\ContactCannotBeProcessedException;
use Domain\Contact\Exceptions\ContactNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        apiPrefix: 'api',
    )
    ->withProviders([
        // Registra nossos Service Providers de infraestrutura
        Infrastructure\Providers\AppServiceProvider::class,
        Infrastructure\Providers\EventServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        // Middleware de aceitação de JSON para todas as rotas de API
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        /*
         * Converte exceções de domínio em respostas JSON padronizadas.
         * Isso centraliza o tratamento de erros evitando try/catch repetitivo nos controllers.
         */

        $exceptions->render(function (ContactNotFoundException $e) {
            return response()->json(
                ['message' => $e->getMessage()],
                JsonResponse::HTTP_NOT_FOUND
            );
        });

        $exceptions->render(function (ContactCannotBeProcessedException $e) {
            return response()->json(
                ['message' => $e->getMessage()],
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY
            );
        });

        $exceptions->render(function (ValidationException $e) {
            return response()->json([
                'message' => 'Os dados fornecidos são inválidos.',
                'errors'  => $e->errors(),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        });
    })
    ->create();

return $app;
