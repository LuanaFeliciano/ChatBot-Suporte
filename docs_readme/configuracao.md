# Configuração

## Objetivo

Documentar as variáveis de ambiente e os arquivos de configuração que controlam
o comportamento do sistema.

## Variáveis de ambiente (`.env`)

### Integrações obrigatórias

| Variável | Descrição |
|---|---|
| `OPENAI_API_KEY` | Chave da API da OpenAI. |
| `OPENAI_VECTOR_STORE_ID` | ID do Vector Store onde os documentos são indexados. |
| `OPENAI_ORGANIZATION` | (Opcional) ID da organização OpenAI. |
| `OPENAI_MODEL` | Modelo usado pelo agente (ex.: `gpt-4o`). Default no código: `gpt-4o`. |
| `TELEGRAM_BOT_TOKEN` | Token do bot obtido via @BotFather. |
| `TELEGRAM_WEBHOOK_SECRET` | Secret usado para validar as requisições recebidas do Telegram. |

### Usuário administrador inicial (seeder)

| Variável | Descrição |
|---|---|
| `ADMIN_EMAIL` | E-mail do admin criado pelo `RolesAndPermissionsSeeder`. Default: `admin@example.com`. |
| `ADMIN_PASSWORD` | Senha do admin inicial. Default: `password`. |

### Infraestrutura

| Variável | Descrição |
|---|---|
| `DB_CONNECTION` e demais `DB_*` | Conexão com o banco. Use **PostgreSQL** (`pgsql`) — ver observação abaixo. |
| `QUEUE_CONNECTION` | Conexão de fila. Padrão `redis`. |
| `CACHE_STORE` | Store de cache padrão (e dos locks). Padrão `database`. |
| `CACHE_LIMITER` | Store usado pelo `RateLimiter` (rate limit do Telegram). Padrão `redis`. |
| `REDIS_HOST` / `REDIS_PORT` / `REDIS_PASSWORD` | Conexão com o Redis (sessão de contexto, idempotência e buffer de mensagens). |
| `APP_LOCALE` | Idioma padrão da aplicação. Padrão `pt_BR`. Ver [internacionalizacao.md](internacionalizacao.md). |

> **PostgreSQL é obrigatório** para o Dashboard de Analytics e a página de
> Knowledge Gaps, que usam SQL específico do PostgreSQL. O `.env.example` já vem
> com `DB_CONNECTION=pgsql`. Ver [analytics-e-knowledge-gaps.md](analytics-e-knowledge-gaps.md)
> e [troubleshooting.md](troubleshooting.md).

## Arquivos de configuração

### `config/services.php`
Credenciais de serviços de terceiros.

- `services.telegram.bot_token` ← `TELEGRAM_BOT_TOKEN`
- `services.telegram.webhook_secret` ← `TELEGRAM_WEBHOOK_SECRET`
- `services.openai.vector_store_id` ← `OPENAI_VECTOR_STORE_ID`
- `services.openai.model` ← `OPENAI_MODEL` (default `gpt-4o`)

### `config/openai.php`
Configuração do cliente `openai-php/laravel`: `api_key`, `organization`,
`project`, `base_uri` e `request_timeout` (padrão 30s).

### `config/suporte.php`
Parâmetros específicos do domínio:

| Chave | Env | Default | Uso |
|---|---|---|---|
| `slow_response_threshold_ms` | `SLOW_RESPONSE_THRESHOLD_MS` | `5000` | Limite (ms) para sinalizar uma resposta como "lenta" no painel. |
| `knowledge_gap_escalation_threshold` | `KNOWLEDGE_GAP_ESCALATION_THRESHOLD` | `0.5` | Razão mínima de escalonamento de uma pergunta recorrente para recomendar nova documentação. Ver [analytics-e-knowledge-gaps.md](analytics-e-knowledge-gaps.md). |

### `config/admin.php`
Credenciais do admin inicial usado pelo seeder:

- `admin.seed_email` ← `ADMIN_EMAIL` (default `admin@example.com`)
- `admin.seed_password` ← `ADMIN_PASSWORD` (default `password`)

## Parâmetros fixos no código (não configuráveis por `.env`)

Alguns comportamentos têm valores definidos diretamente no código:

| Parâmetro | Valor | Local |
|---|---|---|
| Rate limit | 10 msg / 60s por usuário | `RateLimitService` |
| Janela de contexto da sessão | 24h (86.400s) | `SessionService` |
| Idempotência (TTL do lock) | 24h | `IdempotencyService` |
| Buffer de mensagens (TTL) | 5 min (300s) | `TelegramWebhookHandler` |
| Debounce do atendimento | 3s | `TelegramWebhookHandler` |
| Histórico enviado ao agente | últimas 10 trocas | `SupportAgent` |
| `MaxSteps` do agente | 10 | `SupportAgent` |
| Atraso humanizado | 1,5s – 5s | `ProcessChatMessage` |
| Timeout de indexação (polling) | 60s | `DocumentService` |

Para alterá-los, edite o código correspondente.
