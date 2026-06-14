<?php

return [

    'navigation_label' => 'Conversations',
    'model_label' => 'conversation',
    'plural_model_label' => 'conversations',

    'fields' => [
        'created_at' => 'Date',
        'channel' => 'Channel',
        'channel_user' => 'Channel user',
        'user_name' => 'Name',
        'question' => 'Question',
        'answer' => 'Answer',
        'response_ms' => 'Response time',
        'was_helpful' => 'Helpful?',
        'escalated' => 'Escalated',
    ],

    'actions' => [
        'timeline' => 'View conversation',
    ],

    'filters' => [
        'channel' => 'Channel',
        'created_at' => 'Date',
        'created_from' => 'From',
        'created_until' => 'Until',
        'was_helpful' => 'Feedback',
        'resolved' => 'Resolved',
        'not_resolved' => 'Not resolved',
        'unrated' => 'Unrated',
        'slow' => 'Slow responses',
    ],

    'timeline' => [
        'channel' => 'Channel',
        'user' => 'User',
        'empty' => 'No messages yet.',
        'older_history_hint' => "Older messages — outside the assistant's memory window",
        'today' => 'Today',
        'yesterday' => 'Yesterday',
    ],

    'summary' => [
        'average' => 'Average',
        'p95' => 'p95',
    ],

];
