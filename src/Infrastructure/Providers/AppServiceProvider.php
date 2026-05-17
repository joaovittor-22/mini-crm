<?php

declare(strict_types=1);

namespace Infrastructure\Providers;

use Application\Contact\UseCases\CalculateContactScoreUseCase;
use Application\Contact\UseCases\CreateContactUseCase;
use Application\Contact\UseCases\DeleteContactUseCase;
use Application\Contact\UseCases\UpdateContactUseCase;
use Domain\Contact\Contracts\ContactRepositoryInterface;
use Domain\Contact\Services\ScoreCalculatorService;
use Domain\Contact\Services\ScoreRules\EmailScoreRule;
use Domain\Contact\Services\ScoreRules\NameScoreRule;
use Domain\Contact\Services\ScoreRules\PhoneScoreRule;
use Illuminate\Support\ServiceProvider;
use Infrastructure\Persistence\Eloquent\ContactModelObserver;
use Infrastructure\Persistence\Eloquent\Models\ContactModel;
use Infrastructure\Persistence\Eloquent\Repositories\EloquentContactRepository;

/**
 * Service Provider principal da aplicação.
 *
 * Responsável por registrar e configurar todas as dependências no
 * Service Container do Laravel. É aqui que a Inversão de Dependência (DIP)
 * é concretizada: vinculamos as Interfaces (contratos do Domínio)
 * às suas implementações concretas (Infraestrutura).
 *
 * Ao usar a interface ContactRepositoryInterface em construtores de
 * Use Cases, o Laravel injetará automaticamente EloquentContactRepository.
 */
final class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra os bindings no container de injeção de dependência.
     */
    public function register(): void
    {
        // Vincula a interface do repositório à implementação Eloquent
        $this->app->bind(
            ContactRepositoryInterface::class,
            EloquentContactRepository::class
        );

        // Registra o Domain Service de cálculo de score com todas as regras ativas.
        // Para adicionar uma nova regra, basta instanciá-la aqui.
        $this->app->bind(ScoreCalculatorService::class, function () {
            return new ScoreCalculatorService(rules: [
                new EmailScoreRule(),
                new NameScoreRule(),
                new PhoneScoreRule(),
            ]);
        });

        // Registra os Use Cases (o container resolve automaticamente as dependências)
        $this->app->bind(CreateContactUseCase::class);
        $this->app->bind(UpdateContactUseCase::class);
        $this->app->bind(DeleteContactUseCase::class);
        $this->app->bind(CalculateContactScoreUseCase::class);
    }

    /**
     * Executa ações após o boot da aplicação.
     */
    public function boot(): void
    {
        // Registra o Observer do modelo Eloquent
        ContactModel::observe(ContactModelObserver::class);
    }
}
