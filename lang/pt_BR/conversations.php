<?php

return [

    'navigation_label' => 'Conversas',
    'model_label' => 'conversa',
    'plural_model_label' => 'conversas',

    'fields' => [
        'created_at' => 'Data',
        'channel' => 'Canal',
        'channel_user' => 'Usuário do canal',
        'user_name' => 'Nome',
        'question' => 'Pergunta',
        'answer' => 'Resposta',
        'response_ms' => 'Tempo de resposta',
        'was_helpful' => 'Útil?',
        'escalated' => 'Escalado',
    ],

    'actions' => [
        'timeline' => 'Ver conversa',
    ],

    'filters' => [
        'channel' => 'Canal',
        'created_at' => 'Data',
        'created_from' => 'De',
        'created_until' => 'Até',
        'was_helpful' => 'Avaliação',
        'resolved' => 'Resolvido',
        'not_resolved' => 'Não resolvido',
        'unrated' => 'Sem avaliação',
        'slow' => 'Respostas lentas',
    ],

    'timeline' => [
        'channel' => 'Canal',
        'user' => 'Usuário',
        'empty' => 'Nenhuma mensagem ainda.',
        'older_history_hint' => 'Mensagens antigas — fora da janela de memória do assistente',
        'today' => 'Hoje',
        'yesterday' => 'Ontem',
    ],

    'summary' => [
        'average' => 'Média',
        'p95' => 'p95',
    ],

];
