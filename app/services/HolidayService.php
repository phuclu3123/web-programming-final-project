<?php

declare(strict_types=1);

namespace App\Services;

/** Consumes a third-party JSON webservice (no in-app holiday API is published). */
final class HolidayService
{
    public function upcoming(int $year, string $country = 'VN'): array
    {
        // Fixed domain, allowlisted country, bounded year: not a generic URL fetcher.
        if (!preg_match('/^[A-Z]{2}$/', $country) || !in_array($country, ['VN'], true) || $year < 2020 || $year > 2035) return [];
        $cacheDir = dirname(__DIR__, 2) . '/storage/cache';
        $cachePath = $cacheDir . '/holidays-' . $country . '-' . $year . '.json';
        if (is_file($cachePath) && filemtime($cachePath) > time() - 86400) {
            $cached = json_decode((string)file_get_contents($cachePath), true);
            if (is_array($cached)) return $this->sanitize($cached);
        }
        $url = 'https://nagerholidays.com/api/v4/Holidays/' . $country . '/' . $year;
        if (!function_exists('curl_init')) return $this->stale($cachePath);
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 5, CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_HTTPHEADER => ['Accept: application/json'], CURLOPT_USERAGENT => 'CouponHub-Lab/1.0']);
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!is_string($body) || $status !== 200 || strlen($body) > 512000) return $this->stale($cachePath);
        $value = json_decode($body, true);
        if (!is_array($value)) return $this->stale($cachePath);
        $holidays = $this->sanitize($value);
        if (!is_dir($cacheDir)) @mkdir($cacheDir, 0750, true);
        if (is_dir($cacheDir)) @file_put_contents($cachePath, json_encode($holidays, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        return $holidays;
    }
    private function stale(string $cachePath): array
    {
        $value = is_file($cachePath) ? json_decode((string)file_get_contents($cachePath), true) : [];
        return is_array($value) ? $this->sanitize($value) : [];
    }
    private function sanitize(array $raw): array
    {
        $out = [];
        foreach ($raw as $record) {
            if (!is_array($record)) continue;
            $date = (string)($record['date'] ?? '');
            $name = trim((string)($record['name'] ?? ''));
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $name === '') continue;
            $out[] = ['date' => $date, 'name' => mb_substr($name, 0, 120), 'countryCode' => (string)($record['countryCode'] ?? 'VN')];
        }
        usort($out, static fn($a, $b) => strcmp($a['date'], $b['date']));
        return array_slice($out, 0, 80);
    }
}
