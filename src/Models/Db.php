<?php

namespace App\Models;

use PDO;

class Db
{
    private static PDO $connection;

    private static array $settings = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    public static function connect(string $host, string $user, string $pass, string $database): void
    {
        if (!isset(self::$connection)) {
            self::$connection = new PDO(
                "mysql:host=$host;dbname=$database", $user, $pass, self::$settings);
        }
    }

    public static function query(string $sql, array $params = [], bool $onlyOne = false): array|bool
    {
        $output = self::$connection->prepare($sql);
        $output->execute($params);
        return $onlyOne ? $output->fetch() : $output->fetchAll();
    }


    public static function database(): PDO
    {
        return self::$connection;
    }
}