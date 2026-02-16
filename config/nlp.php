<?php
// config/nlp.php

return [
    'api_url' => env('NLP_API_URL', 'http://localhost:5000'),
    'api_key' => env('NLP_API_KEY', 'your-api-key'),
    'timeout' => env('NLP_API_TIMEOUT', 10),
    
    'endpoints' => [
        'health' => '/api/health',
        'process' => '/api/nlp/process',
        'batch' => '/api/nlp/batch',
        'context' => '/api/nlp/context',
        'context_clear' => '/api/nlp/context/{user_id}/clear',
    ],
    
    'retry_attempts' => 3,
    'retry_delay' => 1000, // ms
];