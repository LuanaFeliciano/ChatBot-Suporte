# Agentes de IA

## Objetivo

Descrever o `SupportAgent` — o agente Laravel AI responsável por gerar as
respostas do bot a partir dos documentos indexados (RAG).

## Conceitos

- **Laravel AI SDK** (`laravel/ai`): camada de abstração sobre o provedor de LLM.
  O agente vive em `app/Ai/Agents/` e implementa contratos do SDK.
- **File Search**: ferramenta da OpenAI que pesquisa no Vector Store e injeta
  trechos relevantes no contexto do modelo.
- **RAG**: o modelo só deve responder com base nos documentos recuperados e no
  histórico da conversa.

## `SupportAgent`

`App\Ai\Agents\SupportAgent` implementa `Agent`, `Conversational`,
`HasProviderOptions` e `HasTools`.

| Aspecto | Implementação |
|---|---|
| **Modelo** | `model()` retorna `config('services.openai.model')` (`OPENAI_MODEL`, default `gpt-4o`). |
| **Instruções** | `instructions()` retorna o `SYSTEM_PROMPT` (pt-BR) + o link de suporte (`services.support_ticket_url`). |
| **Ferramentas** | `tools()` registra `FileSearch` apontando para `services.openai.vector_store_id`. |
| **Histórico** | `messages()` carrega as últimas **10** `BotMessage` do par canal+usuário (ordem cronológica), exceto em sessão nova. |
| **Passos** | `#[MaxSteps(10)]`. |
| **Opções do provedor** | Para OpenAI: `['reasoning' => ['effort' => 'high']]`. |

### System prompt (resumo)

O prompt (constante `SYSTEM_PROMPT`, em pt-BR) instrui o agente a:

- Responder **apenas** com base nos documentos recuperados e no histórico.
- Nunca inventar funcionalidades, fluxos, regras ou permissões.
- Nunca mencionar documentação, arquivos, File Search, IA ou mecanismos internos.
- Escalar para o suporte humano **somente** quando: a informação não existe; os
  passos foram seguidos e o problema persiste; ou a situação exige intervenção
  humana.
- Ao escalar, usar exatamente a `ESCALATION_PHRASE`
  (`"Se o problema continuar, entre em contato pelo link informado."`) seguida do
  link de suporte.
- Responder em português do Brasil, em no máximo 3 parágrafos curtos, com listas
  numeradas para procedimentos.

A `ESCALATION_PHRASE` é também o sinal usado pelas métricas para detectar
escalonamento (`BotMessage::isEscalated()` / `scopeEscalated()`).

## Fluxo no atendimento (`ChatService`)

1. **Saudação**: se a mensagem normalizada é uma saudação conhecida (`oi`, `bom
   dia`, `start`, etc.), responde com mensagem fixa **sem** chamar o agente.
2. **Fallback**: se **não** existe nenhum documento com `status = indexed`,
   responde que a base ainda está sendo configurada e orienta o suporte humano.
3. Caso contrário: determina se a sessão é nova (`SessionService`), instancia o
   `SupportAgent`, chama `prompt($question)`, mede o tempo de resposta, limpa a
   resposta e persiste uma `BotMessage`.

### Limpeza de citações (`cleanAnswer`)

A OpenAI marca citações de arquivos com sentinelas Unicode da área privada
(U+E200 / U+E202 / U+E201). O padrão `SupportAgent::FILE_CITATION_PATTERN`
remove essas marcações; um fallback adicional remove marcadores `filecite`
soltos. Assim o usuário nunca vê referências internas.

## Limitação importante — `file_search_hit_count`

O campo `file_search_hit_count` é calculado contando ocorrências do padrão de
citação na resposta, mas o **SDK `laravel/ai` atual não expõe as annotations de
`file_citation`** do File Search. Na prática, o valor fica **sempre 0/null** e
**não** é um sinal confiável de que documentos relevantes foram encontrados.

Por isso, as métricas e a página de Knowledge Gaps **não** usam esse campo para
decisões; usam o **escalonamento** (controlado pelo próprio prompt) como sinal.
Isso pode ser revisto quando o SDK expor os dados de citação.

## Testabilidade

Conforme as diretrizes do projeto, não há wrapper custom sobre o SDK. Para
testar, substitua o agente no container com uma subclasse que sobrescreve o
método de prompt:

```php
$fake = new class('telegram', 'user-1') extends SupportAgent {
    // sobrescreve o comportamento de prompt para retornar uma resposta fixa
};
$this->app->instance(SupportAgent::class, $fake);
```

## Componentes relacionados

- `App\Ai\Agents\SupportAgent`
- `App\Services\ChatService`, `App\Services\SessionService`
- `App\Models\BotMessage`
- Base de conhecimento consumida: [base-de-conhecimento.md](base-de-conhecimento.md)
- Métricas derivadas: [analytics-e-knowledge-gaps.md](analytics-e-knowledge-gaps.md)
