# Mini CRM de Contatos

API REST para gerenciamento de contatos com cálculo de score, construída com Laravel 11 seguindo princípios de DDD, TDD e SOLID.

## Requisitos

- PHP 8.2+
- Composer
- Extensões PHP comuns do Laravel, incluindo `pdo_sqlite`, `mbstring`, `xml`, `curl` e `zip`

No Fedora, uma instalação básica fica assim:

```bash
sudo dnf install php php-cli php-pdo php-mbstring php-xml php-curl php-zip php-bcmath composer
```

## Instalação

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan db:seed
```

## Variáveis de Ambiente

| Variável | Exemplo | Descrição |
| --- | --- | --- |
| `APP_ENV` | `local` | Define o ambiente da aplicação. Em desenvolvimento, use `local`. |
| `APP_KEY` | `base64:...` | Chave usada pelo Laravel para criptografia interna. Gere com `php artisan key:generate`. |
| `APP_DEBUG` | `true` | Controla a exibição de erros detalhados. Use `true` apenas em desenvolvimento. |
| `DB_CONNECTION` | `sqlite` | Define o driver de banco de dados usado pela aplicação. |
| `DB_DATABASE` | `/home/user/projetos/mini-crm/database/database.sqlite` | Caminho absoluto do arquivo SQLite local. |
| `LOG_CHANNEL` | `stack` | Define o canal principal de logs da aplicação. |
| `LOG_STACK` | `single,contact` | Lista os canais agrupados no log principal. |
| `CACHE_STORE` | `file` | Define onde o cache será armazenado. No modo local, usa arquivos. |
| `SESSION_DRIVER` | `file` | Define onde as sessões serão armazenadas. No modo local, usa arquivos. |
| `QUEUE_CONNECTION` | `sync` | Executa jobs imediatamente na própria requisição, sem worker separado. |
| `BROADCAST_CONNECTION` | `log` | Registra eventos de broadcast no log, sem servidor WebSocket. |

## Iniciar

```bash
php artisan serve
```

A API fica disponível em:

```text
http://127.0.0.1:8000/api
```

Teste rápido:

```bash
curl http://127.0.0.1:8000/api/contacts
```

## Testes

```bash
php artisan test
```

Rodar apenas unitários:

```bash
php artisan test --testsuite=Unit
```

Rodar apenas feature tests:

```bash
php artisan test --testsuite=Feature
```

## Endpoints

Base URL:

```text
http://127.0.0.1:8000/api
```

### Criar Contato

```http
POST /api/contacts
```

```json
{
    "name": "Ana Paula Ferreira",
    "email": "ana.paula@empresa.com.br",
    "phone": "(11) 99999-8888"
}
```

### Listar Contatos

```http
GET /api/contacts?page=1&per_page=15
```

### Exibir Contato

```http
GET /api/contacts/{id}
```

### Atualizar Contato

```http
PUT /api/contacts/{id}
```

### Excluir Contato

```http
DELETE /api/contacts/{id}
```

### Processar Score

```http
POST /api/contacts/{id}/process-score
```

Com a configuração local padrão, a fila roda em modo `sync`, então o job é executado na própria requisição.
