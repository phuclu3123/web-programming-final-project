<?php

declare(strict_types=1);
// Secure defaults: 8h absolute, 30m idle. Existing .env values deliberately
// override these; check old 30-day settings before deploying Phase 10B.
$absoluteLifetime = max(300, min(2592000, (int)(getenv('COUPONHUB_SESSION_LIFETIME') ?: 28800)));
$idleLifetime = max(300, min($absoluteLifetime, (int)(getenv('COUPONHUB_SESSION_IDLE') ?: 1800)));

return [
    'otp_secret' => getenv('COUPONHUB_OTP_SECRET') ?: (getenv('OTP_SECRET') ?: ''),
    'session_lifetime' => $absoluteLifetime,
    'session_idle' => $idleLifetime,
    'rate_limit_dir' => getenv('COUPONHUB_RATE_LIMIT_DIR') ?: dirname(__DIR__) . '/storage/cache',
];
