<?php

return [

    'navigation_label' => 'Analytics',

    'filters' => [
        'range' => 'Period',
        'range_7' => 'Last 7 days',
        'range_30' => 'Last 30 days',
        'range_90' => 'Last 90 days',
    ],

    'usage' => [
        'total_conversations' => 'Total conversations',
        'conversations_last_7_days' => 'Conversations (last 7 days)',
        'conversations_last_30_days' => 'Conversations (last 30 days)',
        'unique_users' => 'Unique users',
        'messages_per_channel' => 'Messages — :channel',
        'daily_message_volume' => 'Daily message volume',
    ],

    'ai_performance' => [
        'avg_response_time' => 'Avg. response time',
        'p95_response_time' => 'p95 response time',
        'escalated' => 'Escalated',
        'fresh_sessions' => 'Fresh sessions',
        'channel_avg_response_time' => ':channel — avg. response time',
        'channel_p95_response_time' => ':channel — p95 response time',
    ],

    'knowledge_base' => [
        'heading' => 'Knowledge base health',
        'status_counts' => 'Documents by status',
        'error_documents' => 'Documents with errors',
        'recently_updated' => 'Recently updated documents',
        'total_indexed' => 'Indexed documents',
        'total_indexed_size' => 'Indexed size',
        'no_errors' => 'No documents in error status.',
        'no_recent_updates' => 'No recent updates.',
    ],

];
