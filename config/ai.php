<?php

return [
    'driver' => env('AI_DRIVER', 'simulated'),
    'model' => env('AI_MODEL', 'demo-simulator'),
    'max_prompt_chars' => (int) env('AI_MAX_PROMPT_CHARS', 8000),
    'requests_per_minute' => (int) env('AI_REQUESTS_PER_MINUTE', 10),
    'models' => [
        'simulated' => ['demo-simulator'],
        'openai' => ['gpt-4.1-mini', 'gpt-4.1-nano'],
    ],
    'providers' => [
        'simulated' => [
            'class' => App\Services\Ai\SimulatedAiProvider::class,
            'label' => 'Local simulator (development only)',
            'local_only' => true,
        ],
        'openai' => [
            'class' => App\Services\Ai\OpenAiProvider::class,
            'label' => 'OpenAI',
            'credential_key' => 'api_key',
            'credential_label' => 'OpenAI API key',
        ],
    ],
    'openai' => [
        'base_url' => env('AI_OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'timeout' => (int) env('AI_OPENAI_TIMEOUT', 60),
    ],
];