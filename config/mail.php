<?php

// XAMPP demo: run Mailpit listening on 127.0.0.1:1025 (inbox at :8025).
// For real email, use TLS + a reputable SMTP provider, configured through
// server environment variables. Never commit SMTP passwords into Git.
return [
    'driver' => getenv('COUPONHUB_MAIL_DRIVER') ?: (getenv('RESEND_API_KEY') ? 'resend' : 'smtp'),
    'resend_api_key' => getenv('RESEND_API_KEY') ?: '',
    'resend_from' => getenv('RESEND_FROM_EMAIL') ?: '',
    'host'       => getenv('COUPONHUB_SMTP_HOST') ?: '127.0.0.1',
    'port'       => (int) (getenv('COUPONHUB_SMTP_PORT') ?: 1025),
    'encryption' => getenv('COUPONHUB_SMTP_ENCRYPTION') ?: 'none', // none | tls | ssl
    'username'   => getenv('COUPONHUB_SMTP_USER') ?: '',
    'password'   => getenv('COUPONHUB_SMTP_PASSWORD') ?: '',
    'from_email' => getenv('COUPONHUB_MAIL_FROM') ?: 'no-reply@couponhub.test',
    'timeout'    => 8,
];
