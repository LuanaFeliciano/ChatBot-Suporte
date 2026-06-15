<?php

return [

    'navigation_label' => 'Knowledge Gaps',

    'tabs' => [
        'escalated' => 'Escalated & Unanswered',
        'low_confidence' => 'Low Confidence',
        'recurring' => 'Recurring Questions',
        'not_helpful' => 'Not Resolved (👎)',
    ],

    'fields' => [
        'question' => 'Question',
        'created_at' => 'Date',
        'channel' => 'Channel',
        'user' => 'User',
        'occurrences' => 'Occurrences',
        'first_seen' => 'First seen',
        'last_seen' => 'Last seen',
        'distinct_users' => 'Distinct users',
        'recommendation' => 'Recommendation',
        'has_negative_feedback' => 'Negative feedback',
    ],

    'filters' => [
        'channel' => 'Channel',
        'created_at' => 'Date',
        'created_from' => 'From',
        'created_until' => 'Until',
    ],

    'actions' => [
        'view_conversation' => 'View conversation',
        'view_messages' => 'View messages',
        'back_to_groups' => 'Back to groups',
    ],

    'feedback' => [
        'heading' => 'Feedback',
        'positive' => 'Resolved',
        'negative' => 'Not resolved',
        'unrated' => 'Unrated',
    ],

    'recommendations' => [
        'new_documentation' => 'Create new documentation',
        'review_content' => 'Review existing content',
        'ok' => 'No action needed',
    ],

    'indexing_errors' => ':count document(s) with indexing errors — review the knowledge base.',

];
