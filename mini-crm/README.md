# Mini CRM de Contatos

API REST para gerenciamento de contatos com cálculo assíncrono de score, construída com Laravel 11 seguindo os princípios de Domain-Driven Design (DDD), Test-Driven Development (TDD) e SOLID.

---

## Sumário

- [Requisitos](#requisitos)
- [Instalacao e Setup](#instalacao-e-setup)
- [Rodando os Testes](#rodando-os-testes)
- [Endpoints da API](#endpoints-da-api)
- [Fluxo de Processamento de Score](#fluxo-de-processamento-de-score)
- [WebSocket — Escutando Atualizacoes em Tempo Real](#websocket)
- [Arquitetura do Projeto](#arquitetura-do-projeto)
- [Design Patterns Aplicados](#design-patterns-aplicados)

---

## Requisitos

- Docker e Docker Compose (recomendado)
- OU: PHP 8.2+, Composer, MySQL 8, Redis 7

---

## Instalacao e Setup

### 1. Clone e configure as variáveis de ambiente

```bash
git clone <seu-repositorio> mini-crm
cd mini-crm
cp .env.example .env
```

### 2. Suba os containers Docker

```bash
docker compose up -d
```

Isso inicia: Nginx (porta 8000), PHP-FPM, MySQL, MySQL de teste, Redis, Worker de Filas e Laravel Reverb (porta 8080).

### 3. Instale as dependências PHP

```bash
docker compose exec php composer install
```

### 4. Gere a chave da aplicação

```bash
docker compose exec php php artisan key:generate
```

### 5. Execute as migrations e o seeder

```bash
# Cria as tabelas no banco principal
docker compose exec php php artisan migrate

# Popula com dados de exemplo (opcional)
docker compose exec php php artisan db:seed
```

### 6. Verifique que os serviços estão rodando

```bash
# API disponível em:
curl http://localhost:8000/api/contacts

# Reverb WebSocket disponível na porta 8080
# Worker processando a fila 'contacts' automaticamente (container 'queue')
```

---

## Rodando os Testes

Os testes usam um banco de dados MySQL separado (`mini_crm_test`) para não contaminar os dados de desenvolvimento.

### Rodar toda a suíte

```bash
docker compose exec php php artisan test
```

### Rodar apenas os testes unitários (sem banco)

```bash
docker compose exec php php artisan test --testsuite=Unit
```

### Rodar apenas os testes de Feature (com banco)

```bash
docker compose exec php php artisan test --testsuite=Feature
```

### Com cobertura de código

```bash
docker compose exec php php artisan test --coverage
```

### Sem Docker (ambiente local)

```bash
php artisan test
# ou
./vendor/bin/pest
```

Os testes utilizam as seguintes configurações definidas no `phpunit.xml`:
- `QUEUE_CONNECTION=sync`: Jobs executam imediatamente, sem necessidade de worker
- `BROADCAST_CONNECTION=null`: Broadcasts descartados (sem WebSocket real nos testes)
- `LOG_CHANNEL=null`: Logs suprimidos para não poluir o output
- Banco de dados de teste separado configurado via variáveis de ambiente

---

## Endpoints da API

Base URL: `http://localhost:8000/api`

Todas as respostas usam `Content-Type: application/json`.

### Criar Contato

```
POST /api/contacts
```

Corpo da requisição:
```json
{
    "name":  "Ana Paula Ferreira",
    "email": "ana.paula@empresa.com.br",
    "phone": "(11) 99999-8888"
}
```

Resposta (`201 Created`):
```json
{
    "id":           1,
    "name":         "Ana Paula Ferreira",
    "email":        "ana.paula@empresa.com.br",
    "phone":        "(11) 99999-8888",
    "score":        0,
    "status":       "pending",
    "status_label": "Pendente",
    "processed_at": null
}
```

### Listar Contatos

```
GET /api/contacts?page=1&per_page=15
```

Resposta (`200 OK`):
```json
{
    "data": [...],
    "meta": {
        "total":        25,
        "per_page":     15,
        "current_page": 1,
        "last_page":    2
    }
}
```

### Exibir Contato

```
GET /api/contacts/{id}
```

### Atualizar Contato

```
PUT /api/contacts/{id}
```

Corpo: mesmo formato do POST. Score e status são preservados.

### Excluir Contato (Soft Delete)

```
DELETE /api/contacts/{id}
```

Resposta: `204 No Content`. O registro permanece no banco com `deleted_at` preenchido.

### Processar Score (Gatilho Assíncrono)

```
POST /api/contacts/{id}/process-score
```

Resposta (`202 Accepted`):
```json
{
    "message": "O processamento do score foi enfileirado com sucesso.",
    "contact": { ... }
}
```

O processamento ocorre em background. Use WebSockets para receber a atualização em tempo real.

---

## Fluxo de Processamento de Score

Ao chamar `POST /api/contacts/{id}/process-score`, o seguinte fluxo ocorre:

```
1. Controller enfileira ProcessContactScoreJob (resposta imediata: 202)
2. Worker Redis executa o Job em background
3. Job chama CalculateContactScoreUseCase
4. Use Case muda status para 'processing' e salva
5. ScoreCalculatorService aplica as ScoreRules (Strategy Pattern):
   - EmailScoreRule:  e-mail corporativo (+20), dominio .br (+10)
   - NameScoreRule:   nome composto (+10)
   - PhoneScoreRule:  DDD de SP (+20), outro estado (+10)
6. Score total aplicado, status muda para 'active', processed_at preenchido
7. Evento de dominio ContactScoreProcessed disparado
8. Listeners reagem:
   - LogContactScoreProcessedListener -> grava em storage/logs/contact.log
   - BroadcastContactScoreProcessedListener -> envia via Reverb (WebSocket)
```

### Regras de Pontuação

| Regra                             | Pontos |
|-----------------------------------|--------|
| E-mail de domínio corporativo     | +20    |
| E-mail com domínio .br            | +10    |
| Nome composto (mais de uma palavra)| +10   |
| DDD de São Paulo (11 a 19)        | +20    |
| DDD de outro estado               | +10    |
| **Score máximo possível**         | **60** |

---

## WebSocket

A atualização do score é transmitida via WebSocket pelo Laravel Reverb assim que o processamento é concluído.

### Canal e Evento

- Canal: `contacts.{id}` (público)
- Evento: `.ContactScoreUpdated`

### Exemplo com JavaScript (Pusher Client)

```html
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
    const pusher = new Pusher('mini-crm-key', {
        wsHost:            'localhost',
        wsPort:            8080,
        forceTLS:          false,
        enabledTransports: ['ws', 'wss'],
        cluster:           'mt1',
    });

    // Escuta o canal do contato de ID 1
    const channel = pusher.subscribe('contacts.1');

    // O ponto no início do nome do evento é obrigatório para
    // eventos com nome personalizado (sem o namespace App\Events)
    channel.bind('.ContactScoreUpdated', function(data) {
        console.log('Score atualizado:', data.contact.score);
        console.log('Status:', data.contact.status);
    });
</script>
```

### Demonstração Visual

Abra o arquivo `resources/views/websocket-demo.html` no navegador para ter um painel interativo de monitoramento em tempo real. Ele inclui:

- Indicador de estado da conexão WebSocket
- Painel com score animado e status do contato
- Log de todos os eventos recebidos

Para usar a demo:
1. Crie um contato via API.
2. Abra `resources/views/websocket-demo.html` no navegador.
3. Insira o ID do contato e clique em "Monitorar".
4. Chame `POST /api/contacts/{id}/process-score`.
5. Observe o score e status atualizando em tempo real.

### Estrutura do Payload WebSocket

```json
{
    "contact": {
        "id":           1,
        "name":         "Ana Paula Ferreira",
        "email":        "ana.paula@empresa.com.br",
        "phone":        "(11) 99999-8888",
        "score":        60,
        "status":       "active",
        "status_label": "Ativo",
        "processed_at": "2024-01-15 14:32:10"
    }
}
```

---

## Arquitetura do Projeto

O projeto segue a Arquitetura em Camadas inspirada em DDD e Arquitetura Hexagonal:

```
src/
├── Domain/                        # Camada de Domínio (puro PHP, sem framework)
│   └── Contact/
│       ├── Contracts/             # Interfaces (Ports): Repository, ScoreRule
│       ├── Entities/              # Entidade rica: Contact
│       ├── Enums/                 # ContactStatus
│       ├── Events/                # Eventos de domínio: ContactScoreProcessed
│       ├── Exceptions/            # Excecoes de domínio
│       ├── Services/              # Domain Services e ScoreRules (Strategy)
│       └── ValueObjects/          # Email, Phone, Score
│
├── Application/                   # Camada de Aplicacao (orquestracao)
│   └── Contact/
│       ├── DTOs/                  # CreateContactDTO, UpdateContactDTO
│       └── UseCases/              # Um Use Case por operacao de negocio
│
└── Infrastructure/                # Camada de Infraestrutura (Laravel)
    ├── Events/                    # Eventos de broadcast e seus Listeners
    ├── Http/
    │   ├── Controllers/           # Controllers finos (thin controllers)
    │   ├── Requests/              # Form Requests (validacao HTTP)
    │   └── Resources/             # API Resources (formatacao JSON)
    ├── Persistence/
    │   └── Eloquent/
    │       ├── Models/            # Modelos Eloquent
    │       ├── Repositories/      # Implementacoes de repositório
    │       └── ContactModelObserver.php
    ├── Providers/                 # Service Providers (DI bindings)
    └── Queue/Jobs/                # Jobs de fila
```

### Princípio da Inversão de Dependência

Use Cases dependem da interface `ContactRepositoryInterface` (Domínio), nunca da implementação `EloquentContactRepository` (Infraestrutura). O Service Container do Laravel resolve o binding em `AppServiceProvider`:

```php
$this->app->bind(
    ContactRepositoryInterface::class,
    EloquentContactRepository::class
);
```

---

## Design Patterns Aplicados

### Strategy Pattern — Regras de Score

Cada regra de pontuação é uma classe independente que implementa `ScoreRuleInterface`. Adicionar uma nova regra não requer modificar nenhuma classe existente (Open/Closed Principle):

```
ScoreRuleInterface
├── EmailScoreRule   (+20 corporativo, +10 .br)
├── NameScoreRule    (+10 nome completo)
└── PhoneScoreRule   (+20 SP, +10 outros)
```

O `ScoreCalculatorService` recebe as rules via construtor e as executa em sequência.

### Value Objects — Imutabilidade e Validação

`Email`, `Phone` e `Score` são imutáveis. Toda validação de formato ocorre no construtor, garantindo que objetos inválidos nunca existam:

```php
// Lanca InvalidEmailException se inválido
$email = Email::fromString('invalido');

// Lanca InvalidPhoneException se inválido
$phone = Phone::fromString('123');
```

### Repository Pattern — Separação de Persistência

O repositório traduz entre Entidades de Domínio (puras) e Modelos Eloquent. Use Cases nunca importam `ContactModel` diretamente.

### Observer — Normalização Automática

`ContactModelObserver` normaliza o telefone para somente dígitos no evento `saving`, garantindo consistência no banco independente do formato de entrada.

---

## Logs

Os eventos de score são gravados em `storage/logs/contact.log`:

```
[2024-01-15 14:32:10] local.INFO: Score processado com sucesso.
{"contact_id":1,"email":"ana@empresa.com.br","score":60,"status":"active","processed_at":"2024-01-15 14:32:10"}
```
