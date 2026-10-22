<?php
declare(strict_types=1);
namespace App\Core;

/** Small dotenv reader: configuration only, no eval or variable expansion. */
final class Env
{
    public static function load(string $file): void
    {
        if (!is_file($file)) return;
        foreach (file($file, FILE_IGNORE_NEW_LINES) ?: [] as $number => $line) {
            $line = trim($line);
            if ($number === 0) $line = preg_replace('/^\xEF\xBB\xBF/', '', $line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            if (!preg_match('/^(?:export\s+)?([A-Z][A-Z0-9_]*)\s*=\s*(.*)$/D', $line, $match)) throw new \RuntimeException('Invalid .env syntax at line ' . ($number+1));
            [, $name, $value] = $match;
            if (!preg_match('/^(?:COUPONHUB_|RESEND_|OTP_SECRET$)/', $name)) continue;
            if (getenv($name) !== false) continue; // Process environment wins, including empty values.
            $value = trim($value);
            if ($value !== '' && in_array($value[0], ['"', "'"], true)) {
                if (substr($value, -1) !== $value[0] || strlen($value)<2) throw new \RuntimeException('Unclosed .env quote at line ' . ($number+1));
                $value = substr($value, 1, -1);
            } else $value = preg_replace('/\s+#.*$/', '', $value);
            if (str_contains($value, "\0")) throw new \RuntimeException('Invalid .env value');
            putenv($name . '=' . $value);
        }
    }
}
