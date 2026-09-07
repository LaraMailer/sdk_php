<?php

return [
    'endpoint' => env('LARAMAILER_ENDPOINT', 'http://localhost'),
    'token' => env('LARAMAILER_TOKEN'),
    'account_id' => env('LARAMAILER_ACCOUNT_ID'),
    'timeout' => (int) env('LARAMAILER_TIMEOUT', 15),
    'tracking_enabled' => (bool) env('LARAMAILER_TRACKING_ENABLED', true),
];
