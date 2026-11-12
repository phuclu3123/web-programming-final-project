<?php
// Tạo callback từ URL chuẩn; client secret chỉ được dùng phía server.
declare(strict_types=1);

// OAuth secrets stay on the server; callbacks must match the provider dashboard.
$baseUrl = rtrim(getenv('COUPONHUB_APP_URL') ?: 'http://localhost:8000', '/');
$facebookVersion = getenv('COUPONHUB_FACEBOOK_GRAPH_VERSION') ?: '';
return [
    'base_url' => $baseUrl,
    'google_one_tap' => filter_var(getenv('COUPONHUB_GOOGLE_ONE_TAP') === false ? '1' : getenv('COUPONHUB_GOOGLE_ONE_TAP'), FILTER_VALIDATE_BOOLEAN),
    'providers' => [
        'facebook' => [
            'client_id' => getenv('COUPONHUB_FACEBOOK_CLIENT_ID') ?: '',
            'client_secret' => getenv('COUPONHUB_FACEBOOK_CLIENT_SECRET') ?: '',
            // Pin the supported version selected in the Meta app dashboard.
            'graph_version' => $facebookVersion,
            'callback' => $baseUrl . '/api/oauth/facebook-callback.php',
            'authorize_url' => 'https://www.facebook.com/' . $facebookVersion . '/dialog/oauth',
            // Some Meta apps have not enabled the email use case. Never break
            // an otherwise working Facebook login by requesting it implicitly.
            'scope' => filter_var(getenv('COUPONHUB_FACEBOOK_REQUEST_EMAIL') ?: '0', FILTER_VALIDATE_BOOLEAN) ? 'public_profile email' : 'public_profile',
        ],
        'github' => [
            'client_id' => getenv('COUPONHUB_GITHUB_CLIENT_ID') ?: '',
            'client_secret' => getenv('COUPONHUB_GITHUB_CLIENT_SECRET') ?: '',
            'callback' => $baseUrl . '/api/oauth/github-callback.php',
            'authorize_url' => 'https://github.com/login/oauth/authorize',
            'scope' => 'read:user user:email',
        ],
        // GOOGLE: OAuth client loại Web application trong Google Cloud Console
        'google' => [
            'client_id' => getenv('COUPONHUB_GOOGLE_CLIENT_ID') ?: '',
            'client_secret' => getenv('COUPONHUB_GOOGLE_CLIENT_SECRET') ?: '',
            'callback' => $baseUrl . '/api/oauth/google-callback.php',
            'authorize_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'scope' => 'openid email profile',
        ],
    ],
];
