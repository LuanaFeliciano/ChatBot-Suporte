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

];
