<?php
declare(strict_types=1);
return [
    'accesstrade' => ['access_key' => getenv('ACCESSTRADE_ACCESS_KEY') ?: '', 'limit' => 50],
    'turnstile' => [
        'mode' => getenv('COUPONHUB_TURNSTILE_MODE') ?: 'auto',
        'site_key' => getenv('TURNSTILE_SITE_KEY') ?: '',
        'secret_key' => getenv('TURNSTILE_SECRET_KEY') ?: '',
        'hostnames' => getenv('TURNSTILE_HOSTNAMES') ?: '',
    ],
];
