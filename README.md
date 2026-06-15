# Chatbot Suporte

Chatbot de suporte técnico via **Telegram** com arquitetura **RAG**: responde às
dúvidas dos usuários com base exclusivamente em documentos indexados em um OpenAI
Vector Store. Acompanha um **painel administrativo** (Filament) para gerenciar a
base de conhecimento, explorar conversas, analisar métricas e identificar lacunas
de conhecimento.

**Stack:** Laravel 13 · PHP 8.3+ · Filament v5 · Laravel AI SDK (`laravel/ai`) ·
OpenAI (modelo + Vector Store) · Redis · PostgreSQL · Telegram Bot API · Laravel Sail

## Principais funcionalidades

- **Atendimento RAG** no Telegram com File Search no Vector Store e histórico de
  contexto (últimas 10 trocas, janela de 24h).
- **Agrupamento de mensagens** (debounce de 3s), indicador de "digitando" e atraso
  humanizado antes da resposta.
- **Feedback 👍/👎** em cada resposta, com edição da mensagem.
- **Saudação** com resposta fixa e **fallback** quando a base ainda não tem
  documentos indexados.
- **Gestão da base de conhecimento** por CLI e pelo painel web (upload,
  substituição atômica, reindexação e remoção).
- **Painel administrativo** com RBAC, explorador de conversas, dashboard de
  Analytics, página de Lacunas de Conhecimento, logs de auditoria e interface
  bilíngue (pt-BR / inglês).

## Requisitos

- Docker e Docker Compose (Laravel Sail)
- Conta na OpenAI com um Vector Store criado
- Bot do Telegram criado via [@BotFather](https://t.me/botfather)
- **PostgreSQL** (necessário para o Analytics e a página de Lacunas de Conhecimento)

## Instalação rápida

> Todos os comandos rodam dentro do Sail. Detalhes em [docs_readme/instalacao.md](docs_readme/instalacao.md).

```bash
cp .env.example .env
# Preencha OPENAI_*, TELEGRAM_*, ADMIN_EMAIL/ADMIN_PASSWORD

./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed          # permissões, papéis, admin e canais
./vendor/bin/sail npm install && ./vendor/bin/sail npm run build
./vendor/bin/sail artisan queue:work --queue=chat,default
```

Registre o webhook (expondo a app com ngrok ou similar):

```bash
./vendor/bin/sail artisan telegram:webhook:set https://<sua-url-publica>
```

O painel fica em `/admin` — entre com o usuário criado pelo seeder.

## Configuração

Variáveis essenciais: `OPENAI_API_KEY`, `OPENAI_VECTOR_STORE_ID`, `OPENAI_MODEL`,
`TELEGRAM_BOT_TOKEN`, `TELEGRAM_WEBHOOK_SECRET`, `SUPPORT_TICKET_URL`,
`ADMIN_EMAIL`, `ADMIN_PASSWORD`, além de `DB_*`, `QUEUE_CONNECTION`, `REDIS_*`,
`CACHE_STORE`/`CACHE_LIMITER` e `APP_LOCALE`.

Lista completa e arquivos de configuração: [docs_readme/configuracao.md](docs_readme/configuracao.md).

## Execução local

- App + PostgreSQL + Redis: `./vendor/bin/sail up -d`
- Worker da fila (obrigatório para processar mensagens): `./vendor/bin/sail artisan queue:work --queue=chat,default`
- Build de assets: `./vendor/bin/sail npm run dev`

## Testes

```bash
./vendor/bin/sail artisan test --compact
# filtrar:
./vendor/bin/sail artisan test --compact --filter=NomeDoTeste
```

Mais detalhes em [docs_readme/testes.md](docs_readme/testes.md).

## Documentação

A documentação técnica completa está em [`docs_readme/`](docs_readme/README.md):

| Documento | Conteúdo |
|---|---|
| [visao-geral.md](docs_readme/visao-geral.md) | Objetivo, funcionalidades e papéis |
| [arquitetura.md](docs_readme/arquitetura.md) | Componentes, fluxo da mensagem, Redis/cache e banco |
| [instalacao.md](docs_readme/instalacao.md) | Instalação completa via Sail |
| [configuracao.md](docs_readme/configuracao.md) | Variáveis de ambiente e configs |
| [base-de-conhecimento.md](docs_readme/base-de-conhecimento.md) | Ingestão e gestão de documentos |
| [agentes-de-ia.md](docs_readme/agentes-de-ia.md) | `SupportAgent`, RAG e limitações |
| [canais-e-mensageria.md](docs_readme/canais-e-mensageria.md) | Telegram, feedback, debounce e "digitando" |
| [usuarios-e-permissoes.md](docs_readme/usuarios-e-permissoes.md) | RBAC, policies e auditoria |
| [painel-administrativo.md](docs_readme/painel-administrativo.md) | Recursos do painel Filament |
| [analytics-e-knowledge-gaps.md](docs_readme/analytics-e-knowledge-gaps.md) | Métricas e lacunas de conhecimento |
| [internacionalizacao.md](docs_readme/internacionalizacao.md) | Idiomas e traduções |
| [testes.md](docs_readme/testes.md) | Suíte Pest |
| [troubleshooting.md](docs_readme/troubleshooting.md) | Problemas comuns |
