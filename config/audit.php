<?php

return [
    'queue' => [
        'enabled' => false,
        'connection' => env('AUDIT_QUEUE_CONNECTION', 'sync'),
        'queue' => env('AUDIT_QUEUE_NAME', 'audit-logs'),
    ],
];
