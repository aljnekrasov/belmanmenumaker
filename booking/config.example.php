<?php
return [
    'env' => 'production', // production | development
    'base_url' => 'https://radius.example.com',
    'booking_base_url' => 'https://radius.example.com/booking',

    'db' => [
        'host' => 'localhost',
        'name' => 'radius_booking',
        'user' => 'radius_user',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],

    'mail' => [
        'smtp_host' => '',
        'smtp_port' => 587,
        'smtp_user' => '',
        'smtp_pass' => '',
        'smtp_secure' => 'tls',
        'from_email' => 'noreply@radius.example.com',
        'from_name' => 'Радиус',
        'admin_email' => 'admin@radius.example.com',
    ],

    'payment_provider' => 'stub',

    'booking' => [
        'hold_minutes' => 15,
        'rate_limit_per_minute' => 5,
    ],

    'security' => [
        'session_lifetime' => 3600,
    ],
];
