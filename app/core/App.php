<?php

declare(strict_types=1);

namespace App\Core;

final class App
{
    /** @var array<string,mixed> */
    private static array $config = [];

    /** @param array<string,mixed> $config */
    public static function init(array $config): void
    {
        self::$config = $config;
        date_default_timezone_set((string) self::config('app.timezone', 'UTC'));
        // Session được mở khi xử lý request, không mở khi chỉ nạp class/test CLI.
    }

    public static function config(string $key, $default = null)
    {
        $segments = explode('.', $key);
        $value = self::$config;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}
