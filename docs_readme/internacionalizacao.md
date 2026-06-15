# Internacionalização

## Objetivo

Descrever o suporte a idiomas do painel administrativo.

## Idiomas suportados

O enum `App\Enums\Locale` define os idiomas:

| Caso | Valor | Rótulo |
|---|---|---|
| `PtBr` | `pt_BR` | Português (Brasil) |
| `En` | `en` | English |

O idioma padrão da aplicação é `pt_BR` (`APP_LOCALE`). Novos usuários nascem com
`locale = pt_BR` (default no modelo `User`).

## Como funciona

- A coluna `users.locale` (cast para `Locale`) guarda a preferência de cada
  usuário.
- O middleware `App\Http\Middleware\SetUserLocale`, registrado no painel
  (`AdminPanelProvider`), aplica o locale do usuário autenticado a cada
  requisição.
- O menu do usuário no painel exibe uma ação para trocar de idioma; ela aponta
  para a rota `admin.locale.update` (`LocaleController`), que persiste a nova
  preferência. A opção do idioma atual fica oculta.

## Arquivos de tradução

As strings ficam em `lang/pt_BR/` e `lang/en/`:

| Arquivo | Conteúdo |
|---|---|
| `analytics.php` | Dashboard e widgets de analytics |
| `conversations.php` | Explorador de conversas |
| `documents.php` | Recurso de documentos |
| `users.php` | Recurso de usuários |
| `roles.php` | Recurso de papéis |
| `knowledge_gaps.php` | Página de lacunas de conhecimento |
| `enums.php` | Rótulos de enums (`RoleName`, `PermissionName`, `DocumentStatus`) |

Os arquivos padrão do Laravel (`auth`, `validation`, `passwords`, `pagination`)
também estão traduzidos em `pt_BR`.

## Observações

- A internacionalização cobre o **painel administrativo**. As mensagens do bot no
  Telegram (saudação, fallback, agradecimento de feedback) e o `SYSTEM_PROMPT`
  do agente são fixos em **português do Brasil** no código.
- Os enums que implementam `HasLabel` resolvem seus rótulos via
  `lang/{locale}/enums.php`.

## Componentes relacionados

- `App\Enums\Locale`
- `App\Http\Middleware\SetUserLocale`
- `App\Http\Controllers\LocaleController`
- `App\Providers\Filament\AdminPanelProvider`
- `lang/pt_BR/*`, `lang/en/*`
