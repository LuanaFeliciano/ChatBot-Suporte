# Arquitetura

## Objetivo

Descrever os componentes do sistema, o fluxo completo de uma mensagem, o uso de
Redis e do cache do Laravel, e o esquema do banco de dados.

## Visão de alto nível

O sistema tem duas faces que compartilham o mesmo banco:

1. **Backend de atendimento (RAG)** — recebe mensagens do Telegram, processa em
   fila e responde com base no Vector Store.
2. **Painel administrativo (Filament)** — gestão de conhecimento, conversas,
   usuários, analytics e auditoria.

## Fluxo de uma mensagem (atendimento)

```
Usuário (Telegram)
  → POST /api/webhook/telegram
  → ValidateTelegramWebhook  (valida o header X-Telegram-Bot-Api-Secret-Token)
  → TelegramController        (delega ao handler)
  → TelegramWebhookHandler:
       • callback_query?  → handleFeedback() e retorna  (feedback 👍/👎)
       • IdempotencyService::isDuplicate()  → descarta retries do Telegram
       • RateLimitService::isRateLimited()   → 10 msg/min por usuário
       • RPUSH na lista de pendências (buffer Redis, TTL 5min)
       • INCR no contador de "geração" (generation)
       • dispatch ProcessChatMessage  → fila "chat", delay de 3s (debounce)
  → ProcessChatMessage (worker da fila):
       • descarta se a generation mudou (chegou mensagem mais nova)
       • adquire lock (Cache::lock, 90s) por usuário
       • LPOP de todas as mensagens pendentes e as concatena
       • sendTypingAction (indicador "digitando")
       • ChatService::process()  → SupportAgent → resposta
       • humanDelay (1,5–5s, proporcional ao tamanho da resposta)
       • sendReply com botões de feedback
       • grava channel_message_id na BotMessage
```

Detalhes do feedback, debounce e "digitando" estão em
[canais-e-mensageria.md](canais-e-mensageria.md).

## Componentes principais

| Componente | Responsabilidade |
|---|---|
| `App\Http\Controllers\Webhook\TelegramController` | Recebe o webhook e delega ao handler. |
| `App\Http\Middleware\ValidateTelegramWebhook` | Valida o secret token (`hash_equals`); retorna 401 se inválido. |
| `App\Channels\Telegram\TelegramWebhookHandler` | Idempotência, rate limit, buffer de mensagens e despacho do job; trata feedback. |
| `App\Jobs\ProcessChatMessage` | Job da fila `chat`: agrupa mensagens, chama o `ChatService`, simula digitação e envia a resposta. |
| `App\Services\ChatService` | Orquestra saudação, fallback, sessão, chamada ao agente e persistência da `BotMessage`. |
| `App\Ai\Agents\SupportAgent` | Agente Laravel AI com `FileSearch` e histórico das últimas 10 trocas. Ver [agentes-de-ia.md](agentes-de-ia.md). |
| `App\Services\SessionService` | Janela de contexto de 24h por usuário (Redis direto). |
| `App\Services\IdempotencyService` | Lock de deduplicação de mensagens (Redis direto). |
| `App\Services\RateLimitService` | `RateLimiter::attempt()` — 10 msg/min por usuário. |
| `App\Channels\Telegram\TelegramAdapter` | Implementa `ChannelAdapterInterface` para o Telegram. |
| `App\Services\DocumentService` | Pipeline de upload/indexação/remoção na OpenAI. Ver [base-de-conhecimento.md](base-de-conhecimento.md). |
| `App\Jobs\IndexDocument` | Versão assíncrona da indexação, usada pelo painel web. |
| `App\Services\KnowledgeGapAnalyzer` | Recomenda ações para perguntas recorrentes. Ver [analytics-e-knowledge-gaps.md](analytics-e-knowledge-gaps.md). |

## Extensibilidade de canais

O atendimento é desacoplado do Telegram pela interface
`App\Channels\Contracts\ChannelAdapterInterface`. `AppServiceProvider` faz o bind
da interface para `TelegramAdapter`. Para adicionar um novo canal (ex.: WhatsApp),
implementa-se a interface — sem alterar `SupportAgent`, `ChatService` ou a
infraestrutura. A interface completa está em
[canais-e-mensageria.md](canais-e-mensageria.md).

## Redis vs. Cache do Laravel

O Redis é usado de **duas formas independentes**:

### 1. Acesso direto via facade `Redis` (conexão `default`)
Não passa pelo sistema de cache do Laravel e **não** é afetado por
`CACHE_STORE`/`CACHE_LIMITER`:

- `SessionService` — janela de contexto de 24h por usuário (`SETEX`/`EXISTS`),
  chave `bot_session_{canal}_{sha256(usuario)}`.
- `IdempotencyService` — lock de deduplicação (`SET NX`, TTL 24h).
- `TelegramWebhookHandler` — buffer de mensagens pendentes
  (`RPUSH`/`INCR`/`LPOP`, TTL 5min), chaves `user_pending:*` e `user_gen:*`.

### 2. Cache store do Laravel (`Cache` / `RateLimiter`)
Configurável via `.env`:

- `CACHE_STORE` (padrão `database`) — store padrão da aplicação. Também usado
  pelo `Cache::lock` em `ProcessChatMessage`.
- `CACHE_LIMITER` (padrão `redis`) — store usado por `RateLimitService` via
  `RateLimiter::attempt()`.

Para trocar o backend do rate limit basta alterar `CACHE_LIMITER` — nenhuma
mudança de código é necessária.

## Filas

- O job de atendimento `ProcessChatMessage` é despachado na fila `chat` com
  `delay(3s)` (debounce). O job de indexação web `IndexDocument` usa a fila
  padrão.
- A conexão de fila é definida por `QUEUE_CONNECTION` (padrão `redis` no
  `.env.example`). O worker deve escutar `chat` e `default`
  (ver [instalacao.md](instalacao.md)).

## Esquema do banco de dados

Tabelas relevantes ao domínio (além das tabelas padrão do Laravel e das tabelas
de permissão do spatie):

### `channels`
Canais de mensageria. Seed cria `telegram` e `whatsapp`.

| Coluna | Tipo | Notas |
|---|---|---|
| `id` | bigint PK | |
| `name` | string(100) | único |
| `slug` | string(100) | único (`telegram`, `whatsapp`) |
| `description` | text | nullable |
| `is_active` | boolean | padrão `true` |

### `documents`
Documentos da base de conhecimento. Usa **soft delete**.

| Coluna | Tipo | Notas |
|---|---|---|
| `id` | bigint PK | |
| `name` | string | nome amigável |
| `original_filename` | string | |
| `openai_file_id` | string | único, nullable |
| `vector_store_file_id` | string | único, nullable |
| `status` | string(50) | enum `DocumentStatus` (`pending`/`uploading`/`indexed`/`error`) |
| `attributes` | json | metadados (`module`, `version`, `source_path`) |
| `error_message` | text | nullable |
| `uploaded_by` | string | nullable |
| `file_size` | bigint | nullable |
| `mime_type` | string(100) | nullable |
| `deleted_at` | timestamp | soft delete |

### `bot_messages`
Cada pergunta/resposta do atendimento.

| Coluna | Tipo | Notas |
|---|---|---|
| `id` | bigint PK | |
| `channel_id` | FK → channels | |
| `channel_user` | string | ID do usuário no canal |
| `user_name` | string | nullable |
| `question` | text | |
| `answer` | text | |
| `response_ms` | smallinteger | nullable — **ver limitação abaixo** |
| `was_helpful` | boolean | nullable (feedback 👍/👎) |
| `is_escalated` | boolean | indexado; `true` quando o agente chamou a `EscalateConversationTool` — ver [agentes-de-ia.md](agentes-de-ia.md) |
| `was_fresh_session` | boolean | sessão nova (sem contexto prévio) |
| `file_search_hit_count` | integer | **sempre 0/null** — ver [agentes-de-ia.md](agentes-de-ia.md) |
| `question_normalized` | string | indexado; preenchido por `BotMessageObserver` |
| `channel_message_id` | string | ID da mensagem enviada (para editar no feedback) |

> **Limitação — `response_ms`:** a coluna é `smallInteger` na migration base, o
> que limita o valor a ~32.767 ms (~32s). Respostas mais lentas que isso podem
> estourar a faixa do tipo.

### `audit_logs`
Trilha de auditoria de ações administrativas.

| Coluna | Tipo | Notas |
|---|---|---|
| `id` | bigint PK | |
| `action` | string(100) | enum `AuditAction` |
| `entity_type` | string(100) | enum `AuditEntityType` |
| `entity_id` | bigint | nullable |
| `payload` | json | nullable (snapshot) |
| `performed_by` | string | nullable |

### Outras
- `users` — com campos adicionais de RBAC (`is_active`, `last_login_at`) e
  `locale`. Ver [usuarios-e-permissoes.md](usuarios-e-permissoes.md).
- Tabelas de permissão do spatie (`roles`, `permissions`, pivôs).

> **Dependência de PostgreSQL:** o Dashboard de Analytics e a página de Knowledge
> Gaps usam SQL específico do PostgreSQL (`percentile_cont ... within group`,
> `bool_or`, `count(*) filter (where ...)`, cast `::int`). Use PostgreSQL.
> Ver [troubleshooting.md](troubleshooting.md).
