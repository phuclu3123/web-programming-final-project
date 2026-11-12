<?php
declare(strict_types=1);
namespace App\Core;

/** Small shared request guards for the existing PHP front controller. */
final class RequestSecurity
{
    public static function headers(): void
    {
        // Even public HTML contains session-specific CSRF/login state.
        header('Cache-Control: no-store, private');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Content-Security-Policy: frame-ancestors \'none\'; base-uri \'self\'; object-src \'none\'');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    public static function checkWrite(): void
    {
        // Use the configured canonical URL behind HTTPS termination; do not trust forwarded headers.
        $base = parse_url((string)config('app.base_url', ''));
        $expected = isset($base['host']) ? ($base['scheme'] ?? 'http') . '://' . $base['host'] . (isset($base['port']) ? ':' . $base['port'] : '')
            : (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '');
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if (($origin !== '' && (self::origin($origin) === '' || self::origin($origin) !== self::origin($expected)))
            || ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '') === 'cross-site') throw new HttpError(403, 'Nguồn yêu cầu không hợp lệ.');
        if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 1048576) throw new HttpError(413, 'Dữ liệu gửi lên quá lớn.');
    }

    private static function origin(string $url): string
    {
        $parts = parse_url($url);
        if (!$parts || !isset($parts['scheme'], $parts['host']) || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['query']) || isset($parts['fragment']) || !in_array($parts['path'] ?? '', ['', '/'], true)
            || !in_array($parts['scheme'], ['https', 'http'], true)) return '';
        $port = $parts['port'] ?? ($parts['scheme'] === 'https' ? 443 : 80);
        return $parts['scheme'] . '://' . strtolower($parts['host']) . ':' . $port;
    }

    public static function limitWrite(): void
    {
        AuthRateLimit::hit('write-ip', $_SERVER['REMOTE_ADDR'] ?? 'local', 120, 60);
        $actor = Session::userId();
        if ($actor > 0) AuthRateLimit::hit('write-user', (string)$actor, 60, 60);
    }
}
