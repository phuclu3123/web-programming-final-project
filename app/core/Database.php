<?php

declare(strict_types=1);

namespace App\Core;

use mysqli;
use mysqli_sql_exception;
use RuntimeException;

final class Database
{
    private static ?MysqliConnection $connection = null;

    private function __construct() {}

    public static function connection(): MysqliConnection
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $host = (string) App::config('database.host', '127.0.0.1');
        $port = (int) App::config('database.port', '3306');
        $name = (string) App::config('database.name', 'couponhub');
        $user = (string) App::config('database.user', 'root');
        $password = (string) App::config('database.password', '');
        $charset = (string) App::config('database.charset', 'utf8mb4');

        if (!extension_loaded('mysqli')) {
            throw new RuntimeException('PHP chưa bật extension mysqli. Hãy kích hoạt extension=mysqli trong php.ini của XAMPP.');
        }
        if (!function_exists('mysqli_stmt_get_result')) {
            throw new RuntimeException('PHP MySQLi cần mysqlnd để đọc kết quả prepared statements.');
        }

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            $link = new mysqli($host, $user, $password, $name, $port);
            $link->set_charset($charset);
            // Match stored procedure / view collation and use UTC for SQL times.
            $link->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
            $link->query("SET collation_connection = 'utf8mb4_unicode_ci'");
            $link->query("SET time_zone = '+00:00'");
            self::$connection = new MysqliConnection($link);
        } catch (mysqli_sql_exception $e) {
            if ((bool) App::config('app.debug', false)) {
                throw $e;
            }
            throw new RuntimeException('Không thể kết nối cơ sở dữ liệu.', 0, $e);
        }
        return self::$connection;
    }
}
