# Suporte APP

Chatbot de suporte técnico via Telegram com arquitetura RAG. Responde perguntas dos usuários com base exclusivamente em documentos indexados em um OpenAI Vector Store.

**Stack:** Laravel 13 · Laravel AI SDK · OpenAI (GPT + Vector Store) · Redis · PostgreSQL · Telegram Bot API

---

## Fluxo de uma mensagem

```
Usuário (Telegram)
  → POST /webhook/telegram
  → Validação do secret token (middleware)
  → Verificação de idempotência (Redis, lock 30s)
  → Verificação de rate limit (Redis, 10 msg/min por usuário)
  → SupportAgent — FileSearch no Vector Store + histórico das últimas 10 mensagens
  → Persiste conversa (PostgreSQL)
  → Envia resposta ao usuário
```

---

## Pré-requisitos

- Docker e Docker Compose
- Conta na OpenAI com um Vector Store criado
- Bot do Telegram criado via [@BotFather](https://t.me/botfather)

---

## Setup local

```bash
# 1. Clone e instale as dependências
git clone <repo>
cd chatbot-suporte
composer install

# 2. Configure o ambiente
cp .env.example .env
# Preencha as variáveis obrigatórias (ver seção abaixo)

# 3. Gere a chave da aplicação
./vendor/bin/sail artisan key:generate

# 4. Suba os containers (inclui Redis)
./vendor/bin/sail up -d

# 5. Rode as migrations
./vendor/bin/sail artisan migrate

# 6. Inicie o worker da fila (necessário para processar as mensagens)
./vendor/bin/sail artisan queue:work redis --queue=chat
```

### Registrar o webhook do Telegram

Para desenvolvimento local, exponha a aplicação com [ngrok](https://ngrok.com) ou similar:

```bash
ngrok http 80
```

Com a URL pública em mãos, registre o webhook via Artisan (lê `TELEGRAM_BOT_TOKEN` e `TELEGRAM_WEBHOOK_SECRET` do `.env` automaticamente):

```bash
./vendor/bin/sail artisan telegram:webhook:set https://<sua-url-ngrok>
```

O comando monta a URL final como `https://<sua-url-ngrok>/api/webhook/telegram` e confirma o registro.

### Remover o webhook do Telegram

```bash
./vendor/bin/sail artisan telegram:webhook:remove
```

---

## Variáveis de ambiente

| Variável | Descrição |
|---|---|
| `TELEGRAM_BOT_TOKEN` | Token do bot obtido via @BotFather |
| `TELEGRAM_WEBHOOK_SECRET` | Secret para validar requisições recebidas do Telegram |
| `OPENAI_API_KEY` | Chave da API da OpenAI |
| `OPENAI_VECTOR_STORE_ID` | ID do Vector Store onde os documentos são indexados |
| `OPENAI_MODEL` | Modelo da OpenAI a utilizar (ex: `gpt-4o`) |
| `SUPPORT_TICKET_URL` | URL do suporte humano — exibida quando o bot não encontra resposta |
| `REDIS_HOST` / `REDIS_PORT` | Conexão com Redis (sessões, rate limit, idempotência) |
| `DB_CONNECTION` e demais `DB_*` | Conexão com o banco de dados |

---

## Gerenciamento da base de conhecimento

Todos os comandos são executados via Artisan. Formatos aceitos: **PDF** e **texto plano**.

### Indexar um documento

```bash
./vendor/bin/sail artisan docs:ingest caminho/para/arquivo.pdf \
  --name="Nome amigável" \
  --module="financeiro" \
  --doc-version="2.1"
```

O processo cria o registro local, faz upload para a OpenAI Files API, adiciona ao Vector Store e aguarda a indexação (máximo 60s). Em caso de falha, o erro é registrado no `audit_logs`.

### Atualizar um documento

```bash
./vendor/bin/sail artisan docs:update {id} caminho/para/novo-arquivo.pdf
```

Substitui o arquivo indexado de forma atômica: indexa a nova versão primeiro e só remove a antiga após sucesso — sem janela de indisponibilidade. Os metadados (`name`, `module`, `doc-version`) são herdados do documento original por padrão; use as mesmas opções do `docs:ingest` para sobrescrever:

```bash
./vendor/bin/sail artisan docs:update {id} novo-arquivo.pdf \
  --name="Nome atualizado" \
  --doc-version="2.0"
```

O registro antigo é **soft-deleted** com seus IDs preservados. Um novo registro é criado e seu ID é exibido ao final.

### Listar documentos

```bash
./vendor/bin/sail artisan docs:list
```

Exibe todos os documentos (incluindo removidos) com ID, nome, status, módulo, versão, tamanho e data.

### Verificar status de um documento

```bash
./vendor/bin/sail artisan docs:status {id}
```

Mostra o status atual (`pending`, `uploading`, `indexed`, `error`) e, em caso de erro, a mensagem detalhada.

### Remover um documento

```bash
./vendor/bin/sail artisan docs:delete {id}
```

Remove o arquivo do Vector Store e da OpenAI Files API. O registro local é **soft-deleted** — o histórico de auditoria é preservado.

---

## Arquitetura

### Componentes principais

| Componente | Responsabilidade |
|---|---|
| `TelegramController` | Recebe o webhook e delega ao `TelegramWebhookHandler` |
| `ValidateTelegramWebhook` | Middleware que valida o secret token em toda requisição |
| `TelegramWebhookHandler` | Orquestra idempotência, rate limit e chamada ao `ChatService` |
| `IdempotencyService` | Lock Redis (`SET NX`, TTL 30s) — evita duplicatas em retries do Telegram |
| `RateLimitService` | Contador Redis atômico — máximo 10 mensagens/minuto por usuário |
| `ChatService` | Verifica sessão, instancia o `SupportAgent`, persiste a conversa |
| `SessionService` | Janela de contexto de 24h por usuário via Redis (TTL renovado a cada mensagem) |
| `SupportAgent` | Agente Laravel AI com `FileSearch` no Vector Store e histórico das últimas 10 trocas |
| `DocumentService` | Pipeline de upload e indexação na OpenAI |

### Extensibilidade de canais

O sistema foi desenhado para suportar múltiplos canais. Para adicionar WhatsApp (ou qualquer outro), basta implementar `ChannelAdapterInterface`:

```php
interface ChannelAdapterInterface
{
    public function extractMessage(array $payload): ?array;
    public function sendReply(string $channelUser, string $message): void;
}
```

Nenhuma alteração é necessária no `SupportAgent`, `ChatService` ou serviços de infraestrutura.

---

## Testes

```bash
./vendor/bin/sail artisan test --compact
```

Para filtrar um teste específico:

```bash
./vendor/bin/sail artisan test --compact --filter=NomeDoTeste
```
