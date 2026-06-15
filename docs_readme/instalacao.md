# Instalação

## Objetivo

Colocar o sistema em funcionamento em ambiente de desenvolvimento usando
**Laravel Sail** (Docker), incluindo banco, Redis, migrations, seeders, build de
front-end e registro do webhook do Telegram.

## Pré-requisitos

- Docker e Docker Compose
- Conta na OpenAI com um **Vector Store** já criado (anote o ID)
- Bot do Telegram criado via [@BotFather](https://t.me/botfather) (anote o token)

> Todos os comandos PHP/Artisan/Composer/Node rodam **dentro do Sail**. Sempre
> prefixe com `./vendor/bin/sail`.

## Passo a passo

### 1. Clonar e instalar dependências

```bash
git clone <repo>
cd chatbot-suporte
```

Na primeira execução, instale as dependências do Composer usando uma imagem
temporária (antes de o Sail existir):

```bash
docker run --rm \
  -u "$(id -u):$(id -g)" \
  -v "$(pwd):/var/www/html" \
  -w /var/www/html \
  laravelsail/php83-composer:latest \
  composer install --ignore-platform-reqs
```

### 2. Configurar o ambiente

```bash
cp .env.example .env
```

Preencha as variáveis obrigatórias (ver [configuracao.md](configuracao.md)):
`OPENAI_API_KEY`, `OPENAI_VECTOR_STORE_ID`, `TELEGRAM_BOT_TOKEN`,
`TELEGRAM_WEBHOOK_SECRET`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`.

### 3. Subir os containers

```bash
./vendor/bin/sail up -d
```

Sobe a aplicação, PostgreSQL e Redis.

### 4. Gerar a chave da aplicação

```bash
./vendor/bin/sail artisan key:generate
```

### 5. Rodar as migrations

```bash
./vendor/bin/sail artisan migrate
```

### 6. Rodar os seeders

Cria as permissões, os papéis (`Admin`/`Support`), o usuário administrador
inicial (a partir de `ADMIN_EMAIL`/`ADMIN_PASSWORD`) e os canais
(`telegram`, `whatsapp`):

```bash
./vendor/bin/sail artisan db:seed
```

> Sem este passo não há usuário para acessar o painel nem canais cadastrados,
> e o atendimento falha ao resolver o canal.

### 7. Compilar os assets de front-end

```bash
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

### 8. Iniciar o worker da fila

Necessário para processar as mensagens (fila `chat`) e a indexação web
(fila `default`):

```bash
./vendor/bin/sail artisan queue:work --queue=chat,default
```

## Registrar o webhook do Telegram

Em desenvolvimento, exponha a aplicação com [ngrok](https://ngrok.com) ou similar:

```bash
ngrok http 8080
```

Com a URL pública, registre o webhook (lê `TELEGRAM_BOT_TOKEN` e
`TELEGRAM_WEBHOOK_SECRET` do `.env`):

```bash
./vendor/bin/sail artisan telegram:webhook:set https://<sua-url-ngrok>
```

O comando monta a URL final como `https://<sua-url-ngrok>/api/webhook/telegram`
e envia o `secret_token` ao Telegram.

Para remover:

```bash
./vendor/bin/sail artisan telegram:webhook:remove
```

## Acessar o painel administrativo

O painel fica em `/admin` (ex.: `http://localhost/admin`). Faça login com o
usuário criado pelo seeder. Ver [painel-administrativo.md](painel-administrativo.md).

## Próximos passos

- Configurar variáveis e ajustes finos: [configuracao.md](configuracao.md)
- Indexar documentos na base de conhecimento: [base-de-conhecimento.md](base-de-conhecimento.md)
- Problemas comuns: [troubleshooting.md](troubleshooting.md)
