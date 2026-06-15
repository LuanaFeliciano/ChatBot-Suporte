# Troubleshooting

## Objetivo

Reunir problemas comuns e suas causas/soluções, com base no comportamento real
do sistema.

## Analytics / Knowledge Gaps quebram com erro de SQL

**Sintoma:** erro de SQL ao abrir `/admin/analytics` ou `/admin/knowledge-gaps`
(ex.: função `percentile_cont`, `bool_or` ou `filter (where ...)` desconhecida).

**Causa:** essas páginas usam SQL específico do **PostgreSQL**. Em outro banco
(SQLite/MySQL) as consultas falham.

**Solução:** use PostgreSQL (`DB_CONNECTION=pgsql`). O `.env.example` já vem
configurado assim. Ver [analytics-e-knowledge-gaps.md](analytics-e-knowledge-gaps.md).

## Webhook do Telegram retorna 401

**Causa:** o header `X-Telegram-Bot-Api-Secret-Token` não bate com
`TELEGRAM_WEBHOOK_SECRET` (`ValidateTelegramWebhook`).

**Solução:** garanta que o mesmo secret esteja no `.env` e tenha sido enviado ao
Telegram. Re-registre o webhook:

```bash
./vendor/bin/sail artisan telegram:webhook:set https://<sua-url>
```

## O bot não responde

Verifique, em ordem:

1. **Worker da fila ativo?** As mensagens são processadas por
   `ProcessChatMessage` na fila `chat`:
   ```bash
   ./vendor/bin/sail artisan queue:work --queue=chat,default
   ```
2. **Debounce:** há um atraso proposital de 3s + atraso humanizado (até 5s).
3. **Mensagens agrupadas:** mensagens em sequência rápida são agrupadas; só a
   "geração" mais recente é processada.
4. **Sem documentos indexados:** se nenhuma `Document` está com `status =
   indexed`, o bot responde a mensagem de fallback (configurando a base).
5. **Webhook registrado?** Confira no Telegram (`getWebhookInfo`) ou re-registre.
6. **Redis acessível?** Sessão, idempotência e buffer dependem do Redis.

## Mensagens duplicadas / reprocessadas

O Telegram reenvia atualizações em caso de timeout. `IdempotencyService`
descarta duplicatas por 24h. Se virem duplicatas, verifique a conexão com o
Redis (`default`).

## Resposta de "muitas mensagens" não aparece

A mensagem de rate limit ao usuário está **comentada** no
`TelegramWebhookHandler`. Por padrão, mensagens acima de 10/min são apenas
descartadas. Descomente o trecho para enviá-la.

## Documento fica preso em `error`

**Causa:** falha de upload ou timeout de indexação (polling de 60s no
`DocumentService`).

**Solução:**
- Confira `OPENAI_API_KEY` e `OPENAI_VECTOR_STORE_ID`.
- Veja o erro: `./vendor/bin/sail artisan docs:status {id}`.
- Reindexe: `./vendor/bin/sail artisan docs:delete {id}` + novo `docs:ingest`,
  ou use a ação **Retry** no painel (visível quando o status é `error`).

## `response_ms` com valores estranhos

A coluna `response_ms` é `smallInteger` (máx. ~32.767 ms ≈ 32s). Respostas mais
lentas podem estourar a faixa do tipo. Ver [arquitetura.md](arquitetura.md).

## `file_search_hit_count` está sempre 0

É esperado: o SDK `laravel/ai` atual não expõe as annotations de citação do File
Search. Não use esse campo como sinal de qualidade. Ver
[agentes-de-ia.md](agentes-de-ia.md).

## Erro de Vite / manifest ausente

Compile os assets:

```bash
./vendor/bin/sail npm run build
# ou, em desenvolvimento:
./vendor/bin/sail npm run dev
```

## Login no painel negado

`canAccessPanel` exige usuário **ativo** (`is_active = true`) **e** papel `Admin`
ou `Support`. Rode `db:seed` para criar o admin inicial, ou ative/atribua papel
ao usuário. Ver [usuarios-e-permissoes.md](usuarios-e-permissoes.md).
