<?php
declare(strict_types=1);
return [
    'otp_secret' => getenv('COUPONHUB_OTP_SECRET') ?: (getenv('OTP_SECRET') ?: ''),
    'session_lifetime' => max(300, min(2592000, (int)(getenv('COUPONHUB_SESSION_LIFETIME') ?: 2592000))),
    'session_idle' => max(300, min(2592000, (int)(getenv('COUPONHUB_SESSION_IDLE') ?: 2592000))),
    'rate_limit_dir' => getenv('COUPONHUB_RATE_LIMIT_DIR') ?: dirname(__DIR__) . '/storage/cache',
];
