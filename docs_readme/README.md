# Documentação — Suporte APP

Documentação técnica do chatbot de suporte via Telegram com arquitetura RAG
(Retrieval-Augmented Generation) e painel administrativo Filament.

> **Fonte da verdade:** esta documentação reflete a implementação real do código.
> Em caso de divergência entre documento e código, o código prevalece.

## Índice

| Documento | Conteúdo |
|---|---|
| [visao-geral.md](visao-geral.md) | Objetivo, problema resolvido, funcionalidades e papéis de usuário |
| [arquitetura.md](arquitetura.md) | Componentes, fluxo da mensagem, uso de Redis/cache, esquema do banco |
| [instalacao.md](instalacao.md) | Instalação via Laravel Sail, migrations, seeders, build e webhook |
| [configuracao.md](configuracao.md) | Variáveis de ambiente e arquivos de configuração |
| [base-de-conhecimento.md](base-de-conhecimento.md) | Ingestão e gestão de documentos (CLI e painel web) |
| [agentes-de-ia.md](agentes-de-ia.md) | `SupportAgent`, File Search, prompt, escalonamento e limitações |
| [canais-e-mensageria.md](canais-e-mensageria.md) | Telegram, `ChannelAdapterInterface`, feedback, debounce e "digitando" |
| [usuarios-e-permissoes.md](usuarios-e-permissoes.md) | RBAC, papéis, permissões, policies e logs de auditoria |
| [painel-administrativo.md](painel-administrativo.md) | Recursos Filament: documentos, conversas, usuários e papéis |
| [analytics-e-knowledge-gaps.md](analytics-e-knowledge-gaps.md) | Dashboard, widgets, lacunas de conhecimento e métricas |
| [internacionalizacao.md](internacionalizacao.md) | Idiomas (pt_BR/en), troca de locale e traduções |
| [testes.md](testes.md) | Suíte Pest e execução |
| [troubleshooting.md](troubleshooting.md) | Problemas comuns e soluções |
