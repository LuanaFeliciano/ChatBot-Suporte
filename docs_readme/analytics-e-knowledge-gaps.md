# Analytics e Lacunas de Conhecimento

## Objetivo

Documentar o Dashboard de Analytics, seus widgets e a página de Lacunas de
Conhecimento (Knowledge Gaps), usados para acompanhar o desempenho do bot e
descobrir onde a documentação está faltando.

> **As métricas são calculadas sob demanda** (não há scheduler/jobs agendados —
> `routes/console.php` contém apenas `inspire`). Cada acesso à página executa as
> consultas no banco.
>
> **Requer PostgreSQL** — várias consultas usam SQL específico do PostgreSQL
> (`percentile_cont ... within group`, `bool_or`, `count(*) filter (where ...)`,
> cast `::int`). Ver [troubleshooting.md](troubleshooting.md).

## Dashboard de Analytics

- Rota: `/admin/analytics`. Acesso exige `view-analytics`.
- Filtro de período (`range`): **7**, **30** (padrão) ou **90** dias, aplicado
  aos widgets sensíveis a período.

### Widgets

| Widget | Conteúdo |
|---|---|
| **UsageOverviewWidget** | Total de conversas, conversas nos últimos 7 e 30 dias, usuários únicos e mensagens por canal. |
| **DailyMessageVolumeChart** | Volume diário de mensagens. |
| **AiPerformanceWidget** | Tempo médio e **p95** de resposta, **% escalonado**, **% de sessões novas**; e, por canal, média e p95. |
| **KnowledgeBaseHealthWidget** | Contagem por status de documento, documentos em erro, últimas atualizações (via auditoria), total e tamanho dos indexados. |
| **FeedbackSummaryWidget** | Série temporal de feedback positivo/negativo e percentuais no período. |

Notas:
- O **p95** usa `percentile_cont(0.95) within group (order by response_ms)`
  (PostgreSQL).
- **Escalonamento** é detectado pela coluna `is_escalated`, preenchida quando o
  agente chama a `EscalateConversationTool` (`BotMessage::scopeEscalated`).
- O `KnowledgeBaseHealthWidget` ignora documentos com soft delete (eles não
  representam a base ativa).

## Página de Lacunas de Conhecimento (Knowledge Gaps)

- Rota: `/admin/knowledge-gaps`. Acesso exige `view-knowledge-gaps` **ou**
  `view-feedback`.
- Exibe um resumo de feedback (positivo/negativo/não avaliado) e a contagem de
  documentos em erro de indexação.

### Abas

| Aba | Mostra |
|---|---|
| **escalated** | Mensagens que escalonaram **ou** que não tinham documento indexado no momento (`scopeEscalated` + `scopeNoDocumentIndexed`). |
| **low_confidence** | Mensagens marcadas como **não úteis** pelo usuário em que o agente **não** escalonou (aparentava confiança). |
| **recurring** | Perguntas agrupadas por `question_normalized`, com nº de ocorrências, primeira/última vez, usuários distintos, sinal de feedback negativo e uma **recomendação**. Permite drill-down nas mensagens do grupo. |
| **not_helpful** | Todas as mensagens com feedback negativo. |

### Normalização de perguntas

Para agrupar perguntas recorrentes, o `BotMessageObserver` grava
`question_normalized` no `creating`: minúsculas, remoção de pontuação e
colapso de espaços.

### Recomendações (`KnowledgeGapAnalyzer`)

Para cada grupo recorrente, `recommend(occurrences, escalatedCount, hasNegativeFeedback)`
retorna:

| Condição | Recomendação | Ação sugerida |
|---|---|---|
| `escalatedCount / occurrences ≥ knowledge_gap_escalation_threshold` (padrão 0.5) | `new_documentation` | Criar novo documento |
| Senão, se há feedback negativo | `review_content` | Revisar a base de documentos |
| Senão | `ok` | Nenhuma ação |

> **Por que escalonamento e não "hits" de busca?** O SDK `laravel/ai` atual não
> expõe as annotations de `file_citation` do File Search, então
> `file_search_hit_count` é sempre 0/null e **não** é um sinal confiável. O
> escalonamento, ao contrário, é registrado explicitamente quando o agente chama
> a `EscalateConversationTool` (coluna `is_escalated`). Ver
> [agentes-de-ia.md](agentes-de-ia.md).

## Limitações conhecidas

- Dependência de PostgreSQL (acima).
- Métricas calculadas em tempo de requisição — em bases muito grandes as
  consultas podem ficar lentas (não há cache/pré-agregação).
- `file_search_hit_count` não é utilizável como sinal de qualidade.

## Componentes relacionados

- `App\Filament\Pages\Dashboard` (Analytics), `App\Filament\Pages\KnowledgeGaps`
- `App\Filament\Widgets\*`
- `App\Services\KnowledgeGapAnalyzer`
- `App\Models\BotMessage` (scopes `escalated`, `noDocumentIndexed`, `lowConfidence`)
- `App\Observers\BotMessageObserver`
