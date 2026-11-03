<?php
declare(strict_types=1);
namespace App\Core;

/** File + flock, appropriate for one PHP/XAMPP server; shared by browser sessions. */
final class AuthRateLimit
{
    public static function hit(string $scope, string $identifier, int $maximum, int $seconds): void
    {
        if ($maximum < 1 || $seconds < 1) throw new \InvalidArgumentException('Invalid rate limit policy');
        $dir = (string)config('auth.rate_limit_dir', dirname(__DIR__, 2).'/storage/cache');
        if (!preg_match('~^(?:[A-Za-z]:[\\\\/]|/)~', $dir)) $dir = dirname(__DIR__, 2) . '/' . $dir;
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) throw new HttpError(503, 'Chưa thể xử lý xác thực lúc này.');
        $path = $dir . '/auth-rate-' . hash('sha256', $scope . ':' . $identifier) . '.json';
        $file = fopen($path, 'c+');
        if (!$file) throw new HttpError(503, 'Chưa thể xử lý xác thực lúc này.');
        if (!flock($file, LOCK_EX)) { fclose($file); throw new HttpError(503, 'Chưa thể xử lý xác thực lúc này.'); }
        try {
            $entry = json_decode(stream_get_contents($file) ?: '{}', true);
            $now = time();
            if (!is_array($entry) || !is_int($entry['until'] ?? null) || !is_int($entry['count'] ?? null)
                || $entry['count'] < 0 || $entry['until'] <= $now) $entry = ['until'=>$now+$seconds,'count'=>0];
            if ((int)$entry['count'] >= $maximum) {
                header('Retry-After: ' . max(1, (int)$entry['until']-$now));
                throw new HttpError(429, 'Bạn thao tác quá nhiều lần. Vui lòng thử lại sau.');
            }
            $entry['count']++;
            $encoded = json_encode($entry, JSON_THROW_ON_ERROR);
            if (!rewind($file) || !ftruncate($file, 0) || fwrite($file, $encoded) !== strlen($encoded) || !fflush($file)) throw new HttpError(503, 'Chưa thể xử lý xác thực lúc này.');
        } finally { flock($file, LOCK_UN); fclose($file); }
    }

    public static function request(string $scope, string $account, int $perIp = 60, int $perAccount = 12, int $seconds = 900): void
    {
        self::hit($scope.'-ip', $_SERVER['REMOTE_ADDR'] ?? 'local', $perIp, $seconds);
        if ($account !== '') self::hit($scope.'-account', mb_strtolower(trim($account)), $perAccount, $seconds);
    }
}
