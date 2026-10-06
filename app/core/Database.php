<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    private function __construct()
    {
    }

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $host = (string) App::config('database.host', '127.0.0.1');
        $port = (string) App::config('database.port', '3306');
        $name = (string) App::config('database.name', 'couponhub');
        $user = (string) App::config('database.user', 'root');
        $password = (string) App::config('database.password', '');
        $charset = (string) App::config('database.charset', 'utf8mb4');

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $host,
            $port,
            $name,
            $charset
        );

        try {
            self::$connection = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            // MariaDB/XAMPP may otherwise default the connection to utf8mb4_general_ci,
            // while CouponHub tables/routines use utf8mb4_unicode_ci. Keep all string
            // parameters and comparisons on the same collation to avoid error 1267.
            self::$connection->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
            self::$connection->exec("SET collation_connection = 'utf8mb4_unicode_ci'");
            self::$connection->exec("SET time_zone = '+00:00'");
        } catch (PDOException $e) {
            if ((bool) App::config('app.debug', false)) {
                throw $e;
            }
            throw new RuntimeException('Không thể kết nối cơ sở dữ liệu.');
        }

        return self::$connection;
    }
}
