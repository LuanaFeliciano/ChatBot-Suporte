# Visão Geral

## Objetivo

O **Suporte Chat** é um chatbot de suporte técnico que atende usuários pelo
Telegram e responde dúvidas **exclusivamente** com base em documentos indexados
em um OpenAI Vector Store (arquitetura RAG — Retrieval-Augmented Generation).

Além do atendimento automatizado, o sistema oferece um **painel administrativo**
(Filament) para gerenciar a base de conhecimento, acompanhar conversas, analisar
métricas de desempenho e identificar lacunas de conhecimento.

## Problema que resolve

Equipes de suporte recebem repetidamente as mesmas perguntas cujas respostas já
existem em manuais e documentação interna. O sistema:

- Responde automaticamente com informações confiáveis, sem "alucinar" conteúdo
  que não esteja documentado.
- Escala para o suporte humano quando não encontra a resposta.
- Coleta feedback (👍/👎) e métricas para revelar onde a documentação está
  faltando ou desatualizada (ver [analytics-e-knowledge-gaps.md](analytics-e-knowledge-gaps.md)).

## Principais funcionalidades

### Atendimento (usuário final)
- Respostas RAG via File Search no Vector Store da OpenAI.
- Histórico de contexto por conversa (últimas 10 trocas, janela de 24h).
- Saudação com mensagem fixa e mensagem de fallback quando a base ainda não
  tem documentos indexados.
- Agrupamento de mensagens enviadas em sequência (debounce de 3s).
- Indicador de "digitando" e atraso humanizado antes da resposta.
- Botões de feedback 👍/👎 em cada resposta.

### Administração e suporte (painel web)
- Gestão da base de conhecimento (upload, substituição, reindexação e remoção).
- Explorador de conversas com linha do tempo por usuário.
- Dashboard de Analytics com métricas de uso e desempenho.
- Página de Lacunas de Conhecimento (Knowledge Gaps).
- Gestão de usuários, papéis e permissões (RBAC).
- Logs de auditoria.
- Interface bilíngue (português do Brasil e inglês).

## Papéis de usuário

| Papel | Acesso |
|---|---|
| **Usuário do Telegram** | Interage apenas pelo bot; não tem conta no painel. |
| **Support** | Acessa o painel para ver conversas, lacunas de conhecimento, feedback e consultar a base de conhecimento (somente leitura). |
| **Admin** | Acesso total: tudo do Support + gestão de documentos, usuários, papéis, analytics e auditoria. |

O acesso ao painel exige usuário ativo (`is_active`) **e** papel `Admin` ou
`Support`. Detalhes em [usuarios-e-permissoes.md](usuarios-e-permissoes.md).

## Integrações externas

- **OpenAI** — modelo de linguagem, Files API e Vector Store (File Search).
- **Telegram Bot API** — recepção de mensagens (webhook) e envio de respostas.

## Tecnologias

Laravel 13 · PHP 8.3+ · Filament v5 · Laravel AI SDK (`laravel/ai`) ·
`openai-php/laravel` · spatie/laravel-permission · Redis · PostgreSQL ·
Laravel Sail (Docker). Veja [arquitetura.md](arquitetura.md) para detalhes.
