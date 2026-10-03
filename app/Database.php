<?php
namespace WildTrail;

use PDO;
use PDOException;

final class Database
{
    private PDO $connection;

    public function __construct(string $host='localhost', string $database='wildtrail_db', string $username='root', string $password='')
    {
        $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";
        try {
            $this->connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new PDOException('Database connection failed. Import sql/schema.sql and check database settings.', (int)$e->getCode(), $e);
        }
    }

    public function connection(): PDO
    {
        return $this->connection;
    }

    public function beginTransaction(): bool { return $this->connection->beginTransaction(); }
    public function commit(): bool { return $this->connection->commit(); }
    public function rollBack(): bool { return $this->connection->rollBack(); }
    public function inTransaction(): bool { return $this->connection->inTransaction(); }
}
