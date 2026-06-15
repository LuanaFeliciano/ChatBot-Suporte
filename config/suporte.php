<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Slow Response Threshold
    |--------------------------------------------------------------------------
    |
    | The number of milliseconds after which a bot response is considered
    | slow. Used to flag slow conversations in the admin panel.
    */

    'slow_response_threshold_ms' => env('SLOW_RESPONSE_THRESHOLD_MS', 5000),

    /*
    |--------------------------------------------------------------------------
    | Knowledge Gap Escalation Threshold
    |--------------------------------------------------------------------------
    |
    | The minimum ratio (0-1) of occurrences of a recurring question that
    | resulted in escalation to human support before recommending new
    | documentation.
    */

    'knowledge_gap_escalation_threshold' => env('KNOWLEDGE_GAP_ESCALATION_THRESHOLD', 0.5),

];
