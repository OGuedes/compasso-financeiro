<?php

declare(strict_types=1);

namespace App\Config;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $driver = self::driver();

        try {
            if ($driver === 'sqlite') {
                $database = Env::get('DB_DATABASE', ':memory:');
                self::$connection = new PDO("sqlite:{$database}");
                self::$connection->exec('PRAGMA foreign_keys = ON');
            } else {
                $host = Env::get('DB_HOST', '127.0.0.1');
                $port = Env::get('DB_PORT', '3306');
                $database = Env::get('DB_DATABASE', 'controle_financeiro');
                $username = Env::get('DB_USERNAME', 'root');
                $password = Env::get('DB_PASSWORD', '');
                $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
                self::$connection = new PDO($dsn, $username, $password);
            }

            self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            self::$connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            if ($driver === 'sqlite') {
                self::initializeSqlite(self::$connection);
            }
            return self::$connection;
        } catch (PDOException $exception) {
            throw new RuntimeException('Não foi possível conectar ao banco de dados.', 0, $exception);
        }
    }

    public static function driver(): string
    {
        return Env::get('DB_DRIVER', 'mysql') ?? 'mysql';
    }

    public static function reset(): void
    {
        self::$connection = null;
    }

    private static function initializeSqlite(PDO $pdo): void
    {
        $schema = dirname(__DIR__, 2) . '/database/schema.sqlite.sql';
        if (!is_file($schema)) {
            throw new RuntimeException('Schema SQLite não encontrado.');
        }
        $pdo->exec(file_get_contents($schema) ?: '');
    }
}
