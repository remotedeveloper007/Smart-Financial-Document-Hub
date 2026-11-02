<?php

use Laravel\Horizon\Exception\HorizonException;

return [
    'use' => 'default',

    'prefix' => env('HORIZON_PREFIX', 'horizon:'),

    'middleware' => ['web', 'auth', 'can:viewHorizon'],

    'waits' => [
        'redis:default' => 60,
    ],

    'trim' => [
        'recent' => 60,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    'environments' => [
        'production' => [
            'supervisors' => [
                'default' => [
                    'connection' => 'redis',
                    'queue' => ['default', 'high', 'ocr', 'exports'],
                    'balance' => 'auto',
                    'processes' => 5,
                    'tries' => 3,
                ],
                'ocr' => [
                    'connection' => 'redis',
                    'queue' => ['ocr'],
                    'balance' => 'simple',
                    'processes' => 4,
                    'tries' => 3,
                ],
                'exports' => [
                    'connection' => 'redis',
                    'queue' => ['exports'],
                    'balance' => 'simple',
                    'processes' => 2,
                    'tries' => 3,
                ],
            ],
        ],

        'local' => [
            'supervisors' => [
                'default' => [
                    'connection' => 'redis',
                    'queue' => ['default'],
                    'balance' => 'simple',
                    'processes' => 1,
                    'tries' => 1,
                ],
            ],
        ],
    ],
];
