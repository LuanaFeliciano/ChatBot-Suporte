# Testes

## Objetivo

Descrever a suíte de testes automatizados (Pest) e como executá-la.

## Visão geral

- Framework: **Pest** (sobre PHPUnit). São **81** arquivos de teste.
- Suítes definidas em `phpunit.xml`:
  - **Unit** (`tests/Unit`)
  - **Feature** (`tests/Feature`)
  - **Architecture** (`tests/Architecture`)

### Ambiente de teste (`phpunit.xml`)

Durante os testes, o ambiente é isolado:

| Variável | Valor |
|---|---|
| `APP_ENV` | `testing` |
| `DB_DATABASE` | `testing` |
| `QUEUE_CONNECTION` | `sync` (jobs rodam imediatamente) |
| `CACHE_STORE` | `array` |
| `SESSION_DRIVER` | `array` |
| `BCRYPT_ROUNDS` | `4` |

> `QUEUE_CONNECTION=sync` significa que `ProcessChatMessage` e `IndexDocument`
> executam de forma síncrona nos testes.

## Como executar

> Sempre via Sail.

```bash
# Toda a suíte
./vendor/bin/sail artisan test --compact

# Filtrar por nome
./vendor/bin/sail artisan test --compact --filter=ChatServiceTest
```

## Cobertura por área

| Área | Exemplos |
|---|---|
| **Arquitetura** | `ChannelAdapterArchTest` (contrato de canais), `RbacArchTest` (regras de RBAC), `ArchTest`. |
| **Webhook/Canais** | validação de assinatura, handler, controller, adapter do Telegram, feedback. |
| **Serviços** | `ChatService`, `DocumentService` (ingest/retry/replace/delete), `SessionService`, `IdempotencyService`, `RateLimitService`, `KnowledgeGapAnalyzer`. |
| **IA** | `SupportAgentTest`, `FallbackTest`, fresh session, file search hit count. |
| **Banco/Migrations/Seeders** | colunas adicionais de `bot_messages`, tabelas de permissão, campos RBAC de usuários, seeders. |
| **Comandos** | `docs:ingest/list/status/delete/update`. |
| **Modelos** | escalonamento, scopes de lacunas, observer de normalização, papéis. |
| **Filament** | acesso ao painel, documentos (upload/replace/retry/delete), conversas/timeline, usuários, papéis (incl. escalonamento de privilégio), analytics, knowledge gaps. |
| **Policies** | `PolicyAuthorizationTest`. |
| **Auditoria** | `AuditLogTest`. |
| **i18n** | `LocaleSwitchingTest`, localização de recursos. |

## Convenções

- Novas funcionalidades **devem** vir com testes Pest (ver `AGENTS.md`).
- Use factories ao criar modelos nos testes.
- Para testes de Filament, autentique com `actingAs` antes de exercitar páginas.

## Componentes relacionados

- `tests/` (suítes Unit/Feature/Architecture)
- `tests/Pest.php`, `tests/TestCase.php`
- `phpunit.xml`
