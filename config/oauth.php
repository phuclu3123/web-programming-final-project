<?php
declare(strict_types=1);

// ---- CHUẨN BỊ OAUTH: chỉ đọc biến môi trường, không ghi secret vào Git ----
$baseUrl = rtrim(getenv('COUPONHUB_APP_URL') ?: 'http://localhost:8000', '/');
return [
    'base_url' => $baseUrl,
    'providers' => [
        'github' => [
            'client_id' => getenv('COUPONHUB_GITHUB_CLIENT_ID') ?: '',
            'client_secret' => getenv('COUPONHUB_GITHUB_CLIENT_SECRET') ?: '',
            'callback' => $baseUrl . '/api/oauth/github-callback.php',
            'authorize_url' => 'https://github.com/login/oauth/authorize',
            'scope' => 'read:user user:email',
        ],
        // ---- GOOGLE: OAuth client loại Web application trong Google Cloud Console ----
        'google' => [
            'client_id' => getenv('COUPONHUB_GOOGLE_CLIENT_ID') ?: '',
            'client_secret' => getenv('COUPONHUB_GOOGLE_CLIENT_SECRET') ?: '',
            'callback' => $baseUrl . '/api/oauth/google-callback.php',
            'authorize_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'scope' => 'openid email profile',
        ],
    ],
];
