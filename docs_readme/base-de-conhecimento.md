# Base de Conhecimento

## Objetivo

Explicar como os documentos que alimentam as respostas do bot são indexados,
atualizados e removidos — tanto pela linha de comando quanto pelo painel web.

## Conceitos

- **Documento** (`documents`): registro local que referencia um arquivo enviado
  à OpenAI. Tem `status` (`pending` → `uploading` → `indexed`, ou `error`) e
  metadados opcionais (`module`, `version`).
- **OpenAI Files API**: onde o arquivo bruto é enviado (`purpose: assistants`).
- **Vector Store**: índice vetorial da OpenAI consultado pelo File Search do
  agente. O ID vem de `OPENAI_VECTOR_STORE_ID`.
- **Soft delete**: documentos removidos não são apagados do banco; ficam com
  `deleted_at` preenchido, preservando o histórico de auditoria.

Formatos aceitos: **PDF** (`application/pdf`) e **texto plano** (`text/plain`).

## Pipeline de indexação (`DocumentService`)

1. Registra/atualiza o `source_path` em `attributes`.
2. Se ainda não houver `openai_file_id`, faz upload para a Files API
   (`status` → `uploading`).
3. Adiciona o arquivo ao Vector Store (`vector_store_file_id`).
4. Faz **polling** do status de indexação por até **60s** (intervalo de 2s).
5. Em sucesso → `status: indexed`; em falha/timeout → `status: error` com
   `error_message`.
6. Registra um log de auditoria (`document.uploaded` ou `document.error`).

A reindexação (`retry`) retoma do passo do Vector Store quando o
`openai_file_id` já existe; caso contrário, reenvia o arquivo.

### Substituição atômica (`replace` / `docs:update`)

A nova versão é indexada **primeiro**; só após o sucesso o documento antigo é
removido do Vector Store e da Files API e **soft-deleted** (log
`document.updated`). Se a nova indexação falhar, o documento antigo permanece
intacto — sem janela de indisponibilidade. Os metadados (`name`, `module`,
`version`) são herdados do documento original, salvo se sobrescritos.

## Dois caminhos de ingestão

| Caminho | Como roda | Componente |
|---|---|---|
| **CLI** (`docs:*`) | **Síncrono** — bloqueia até concluir/falhar | `DocumentService` direto |
| **Painel web** (Filament) | **Assíncrono** — enfileira `IndexDocument` (fila `default`) | `IndexDocument` job → `DocumentService` |

O job `IndexDocument` tem `tries=3`, `timeout=120s` e `backoff=[5,15,30]`.
A tela de documentos faz polling a cada 5s para refletir a mudança de status.

## Comandos Artisan

> Execute via Sail: `./vendor/bin/sail artisan <comando>`.

### Indexar um documento

```bash
docs:ingest caminho/para/arquivo.pdf \
  --name="Nome amigável" \
  --module="financeiro" \
  --doc-version="2.1"
```

`--name` assume o nome do arquivo se omitido. Tipos não suportados são
rejeitados antes do upload.

### Atualizar (substituir) um documento

```bash
docs:update {document_id} caminho/para/novo-arquivo.pdf \
  --name="Nome atualizado" \
  --doc-version="2.0"
```

Substituição atômica (ver acima). Exibe o ID do novo registro ao final.

### Listar documentos

```bash
docs:list
```

### Verificar status

```bash
docs:status {document_id}
```

Mostra `pending`/`uploading`/`indexed`/`error` e, em caso de erro, a mensagem.

### Remover um documento

```bash
docs:delete {document_id}
```

Remove o arquivo do Vector Store e da Files API e faz soft delete do registro
(log `document.deleted`).

## Gestão pelo painel web

O recurso **Documentos** do Filament permite upload, substituição, reindexação
(`retry`) e remoção, todos pela fila. Detalhes da interface em
[painel-administrativo.md](painel-administrativo.md).

## Limitações conhecidas

- **Formatos**: apenas PDF e texto plano.
- **Timeout de indexação**: 60s no polling síncrono; arquivos muito grandes
  podem expirar e cair em `error` — use `retry` para retomar.
- **Indexação CLI é bloqueante**: o terminal fica preso até o fim do processo.

## Componentes relacionados

- `App\Services\DocumentService`
- `App\Jobs\IndexDocument`
- `App\Console\Commands\Docs*`
- `App\Models\Document`, `App\Enums\DocumentStatus`
- Consumo no atendimento: [agentes-de-ia.md](agentes-de-ia.md)
