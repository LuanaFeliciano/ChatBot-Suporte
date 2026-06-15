# Canais e Mensageria

## Objetivo

Descrever a integração com o Telegram, a abstração de canais, o tratamento de
feedback e os mecanismos de debounce, "digitando" e atraso humanizado.

## Abstração de canais

O atendimento é desacoplado do canal concreto pela interface
`App\Channels\Contracts\ChannelAdapterInterface`:

```php
interface ChannelAdapterInterface
{
    // Normaliza o payload do canal; retorna null para updates não-texto.
    // Shape: ['message_id' => string, 'channel_user' => string,
    //         'user_name' => string|null, 'text' => string]
    public function extractMessage(array $payload): ?array;

    // Envia resposta em texto e retorna o ID da mensagem no canal.
    // Com $botMessageId, anexa os controles de feedback (👍/👎).
    public function sendReply(string $channelUser, string $text, ?int $botMessageId = null): string;

    // Indicador "digitando…" (best-effort).
    public function sendTypingAction(string $channelUser): void;

    // Edita uma mensagem já enviada, removendo os controles anexados.
    public function editMessage(string $channelUser, string $channelMessageId, string $text): void;

    // Confirma um callback/interação (dispensa o estado de carregamento).
    public function answerCallback(string $callbackId, ?string $text = null): void;
}
```

`AppServiceProvider` faz o bind `ChannelAdapterInterface → TelegramAdapter`.
Para um novo canal (ex.: WhatsApp), basta implementar a interface — sem alterar
`SupportAgent`, `ChatService` ou a infraestrutura. O `ChannelSeeder` já cadastra
os canais `telegram` e `whatsapp`.

## Recepção (webhook)

- Rota: `POST /api/webhook/telegram` (`routes/api.php`).
- Middleware `ValidateTelegramWebhook`: compara o header
  `X-Telegram-Bot-Api-Secret-Token` com `TELEGRAM_WEBHOOK_SECRET` usando
  `hash_equals`; retorna **401** se inválido ou ausente.
- `TelegramController` delega ao `TelegramWebhookHandler`.

### Ordem de tratamento no handler

1. Se o payload tem `callback_query` → trata feedback e retorna.
2. `extractMessage` ignora updates sem texto (fotos, stickers, etc.).
3. **Idempotência**: descarta mensagens duplicadas (retries do Telegram).
4. **Rate limit**: 10 msg/min por usuário. A mensagem de aviso ao usuário existe,
   mas está **comentada** no código (descomente para ativá-la); por padrão a
   mensagem excedente é apenas descartada.
5. Empilha a mensagem no buffer Redis (`RPUSH`, TTL 5min) e incrementa o
   contador de **geração** (`INCR`).
6. Despacha `ProcessChatMessage` na fila `chat` com `delay(3s)`.

## Debounce e agrupamento

Mensagens enviadas em sequência rápida pelo mesmo usuário são agrupadas:

- Cada mensagem incrementa a "geração". O job só processa se a geração ainda for
  a mais recente quando ele roda (após os 3s de delay) — caso contrário, ele se
  descarta, deixando o job mais novo agrupar tudo.
- O job adquire um lock por usuário (`Cache::lock`, 90s), faz `LPOP` de todas as
  pendências e as concatena em uma única pergunta.

## "Digitando" e atraso humanizado

Em `ProcessChatMessage`:

- `sendTypingAction` é chamado **antes** de gerar a resposta e **novamente**
  logo antes de enviá-la (para o indicador ficar visível na hora certa).
- `humanDelay` aguarda entre **1,5s e 5s**, proporcional ao tamanho da resposta
  (`mb_strlen / 200`, limitado à faixa).

## Envio e formatação

`TelegramAdapter::sendReply` envia via `sendMessage` com `parse_mode: HTML`.
O método `toHtml` converte Markdown simples para HTML:

| Markdown | HTML |
|---|---|
| `**texto**` | `<b>texto</b>` |
| `*texto*` ou `_texto_` | `<i>texto</i>` |
| `` `texto` `` | `<code>texto</code>` |

Quando há `botMessageId`, anexa um teclado inline com os botões
**👍 Resolvido** (`feedback:{id}:up`) e **👎 Não resolvido** (`feedback:{id}:down`).
O ID da mensagem enviada é guardado em `bot_messages.channel_message_id`.

## Feedback (👍/👎)

Quando o usuário toca em um botão, o Telegram envia um `callback_query`:

1. `handleFeedback` valida o padrão `feedback:{id}:(up|down)`.
2. Localiza a `BotMessage` e chama `ChatService::recordFeedback`, que grava
   `was_helpful` **apenas se ainda não houver avaliação** (não sobrescreve).
3. Se era a primeira avaliação, edita a mensagem original adicionando
   "Obrigado pelo retorno!" e remove os botões.
4. `answerCallback` confirma a interação ao Telegram.

O feedback alimenta as métricas e a página de Knowledge Gaps
([analytics-e-knowledge-gaps.md](analytics-e-knowledge-gaps.md)).

## Limitações conhecidas

- O `toHtml` cobre apenas negrito, itálico e código inline — não trata listas,
  links ou blocos de código.
- A mensagem de rate limit ao usuário está desativada (comentada) por padrão.
- Apenas mensagens de **texto** são processadas.

## Componentes relacionados

- `App\Channels\Telegram\TelegramAdapter`, `TelegramWebhookHandler`
- `App\Http\Controllers\Webhook\TelegramController`
- `App\Http\Middleware\ValidateTelegramWebhook`
- `App\Jobs\ProcessChatMessage`
- `App\Services\IdempotencyService`, `RateLimitService`, `SessionService`
