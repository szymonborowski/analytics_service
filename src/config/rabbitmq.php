<?php

return [
    'host' => env('RABBITMQ_HOST', 'rabbitmq'),
    'port' => env('RABBITMQ_PORT', 5672),
    'user' => env('RABBITMQ_USER', 'guest'),
    'password' => env('RABBITMQ_PASSWORD', 'guest'),
    'vhost' => env('RABBITMQ_VHOST', '/'),

    'exchanges' => [
        'analytics' => env('RABBITMQ_EXCHANGE_ANALYTICS', 'analytics'),
    ],

    'queues' => [
        'post_views' => env('RABBITMQ_QUEUE_POST_VIEWS', 'analytics.post_views'),
    ],
];
