# Painel Administrativo

## Objetivo

Apresentar o painel Filament (`/admin`) e seus recursos: documentos, conversas,
usuários e papéis. Analytics e Knowledge Gaps têm documento próprio
([analytics-e-knowledge-gaps.md](analytics-e-knowledge-gaps.md)).

## Acesso

- URL: `/admin` (com tela de login).
- Painel configurado em `App\Providers\Filament\AdminPanelProvider`
  (id `admin`, cor primária Amber, tema Vite próprio).
- Acesso restrito por `canAccessPanel` (usuário ativo + papel Admin/Support) —
  ver [usuarios-e-permissoes.md](usuarios-e-permissoes.md).
- Middleware `SetUserLocale` aplica o idioma do usuário a cada requisição.
- O menu do usuário oferece troca de idioma (pt-BR / inglês).

Há duas páginas de dashboard: o **Dashboard** padrão do Filament (`/admin`,
ver seção abaixo) e o **Analytics**
([analytics-e-knowledge-gaps.md](analytics-e-knowledge-gaps.md)).

## Dashboard (`/admin`)

A página inicial do painel exibe o `DashboardOverviewWidget`: um resumo leve
(sem gráficos) com cards e atalhos que respondem a "o que precisa da minha
atenção agora?". Renderiza junto com a página (`$isLazy = false`) e o
conteúdo varia por papel.

### Admin (`view-analytics`)

| Card | Conteúdo |
|---|---|
| Conversas hoje | Mensagens de hoje |
| Conversas nos últimos 7 dias | Mensagens dos últimos 7 dias |
| Lacunas de conhecimento pendentes | Escalonadas ou sem documento indexado (mesmo critério do Knowledge Gaps) |
| Documentos indexados | Total com `status = indexed` |
| Última indexação | Data/hora relativa do último `document.uploaded`/`document.updated` na auditoria, ou "Nunca" |

Atalhos: Conversas, Lacunas de Conhecimento, Base de Conhecimento, Análises.

### Support

| Card | Conteúdo |
|---|---|
| Conversas abertas recentemente | Mensagens nas últimas 24h |
| Lacunas pendentes de revisão | Escalonadas ou sem documento indexado |
| Documentos na fila de processamento | Documentos com `status` `pending` ou `uploading` |

Atalhos: Conversas, Lacunas de Conhecimento, Base de Conhecimento (acesso
somente leitura via `view-documents`).

> Cada atalho só aparece se o usuário tiver a permissão correspondente
> (`view-conversations`, `KnowledgeGaps::canAccess()`,
> `DocumentResource::canViewAny()`, `view-analytics`).

## Recurso: Documentos

CRUD da base de conhecimento (`DocumentResource`). A indexação no painel é
**assíncrona** (enfileira `IndexDocument`); a tabela faz **polling a cada 5s**.

A lista e a visualização (**View**) exigem `manage-documents` **ou**
`view-documents` — o Support tem acesso de leitura à base de conhecimento.
Upload, substituição, reindexação e remoção exigem `manage-documents`.

### Tabela e filtros
- Colunas: ID, nome, arquivo original, status (badge), módulo, versão, tamanho,
  data de criação e de remoção.
- Filtros: por status, por módulo (derivado de `attributes->module`) e
  `TrashedFilter` (incluir removidos).

### Ações por registro
| Ação | Comportamento | Permissão |
|---|---|---|
| **View** | Visualiza os detalhes do documento. | `manage-documents` ou `view-documents` |
| **Replace** | Faz upload de um novo arquivo (PDF/texto) e dispara substituição atômica via `IndexDocument` (herda metadados, permite sobrescrever nome/módulo/versão). | `manage-documents` |
| **Retry** | Visível apenas quando `status = error`; reexecuta a indexação. | `manage-documents` |
| **Delete** | Visível para registros não removidos; remove do Vector Store/Files API e faz soft delete. | `manage-documents` |

### Ações em massa (exigem `manage-documents`)
`Delete`, `Force delete` e `Restore` (soft delete).

O pipeline de indexação é detalhado em [base-de-conhecimento.md](base-de-conhecimento.md).

## Recurso: Conversas (Bot Messages)

`BotMessageResource` é **somente leitura** (a policy bloqueia create/update/delete).

### Páginas
- **Lista** (`/`): tabela de mensagens, com métricas de latência e coluna de
  feedback.
- **Visualizar** (`/{record}`): detalhes de uma mensagem (infolist).
- **Linha do tempo** (`/{channel}/{channelUser}/timeline`): histórico cronológico
  completo de um usuário em um canal. Destaca a "janela recente"
  (`RECENT_WINDOW = 10` — as 10 últimas trocas, que correspondem ao contexto
  efetivamente enviado ao agente) e indica se há histórico mais antigo.

Acesso exige `view-conversations`.

## Recurso: Usuários

`UserResource` — CRUD de usuários (exige `manage-users`). A edição:
- Atribui exatamente um papel por usuário (via campo `role`, sincronizado em
  `afterSave`).
- Gera logs de auditoria (`user.updated`, e `user.deactivated`/`user.role_changed`
  conforme o caso).
- Bloqueia a exclusão do **último** admin.

## Recurso: Papéis

`RoleResource` — gestão de papéis do spatie (exige `manage-roles`). A edição de
permissões gera log `role.permissions_updated`. As permissões do papel **Admin**
são imutáveis pela interface.

## Widgets (Analytics)

Os widgets aparecem no Dashboard de Analytics e exigem `view-analytics`:
`UsageOverviewWidget`, `DailyMessageVolumeChart`, `AiPerformanceWidget`,
`KnowledgeBaseHealthWidget`, `FeedbackSummaryWidget`. Detalhes em
[analytics-e-knowledge-gaps.md](analytics-e-knowledge-gaps.md).

## Componentes relacionados

- `App\Providers\Filament\AdminPanelProvider`
- `App\Filament\Resources\{Documents,BotMessages,Users,Roles}`
- `App\Filament\Pages\{Dashboard,KnowledgeGaps}`
- `App\Filament\Widgets\DashboardOverviewWidget` (Dashboard padrão, `/admin`)
- `App\Filament\Widgets\*` (demais widgets, Analytics)
