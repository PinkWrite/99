<?php
declare(strict_types=1);

/** Thin PDO wrapper. Prepared statements only. */
final class Db
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public static function connect(array $c): self
    {
        $host = (string) ($c['host'] ?? '127.0.0.1');
        if (strtolower($host) === 'localhost') {
            $host = '127.0.0.1';
        }
        $port = (int) ($c['port'] ?? 3306);
        $name = (string) $c['name'];
        $charset = (string) ($c['charset'] ?? 'utf8mb4');
        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
        $pdo = new PDO($dsn, (string) $c['user'], (string) $c['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return new self($pdo);
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function run(string $sql, array $params = []): PDOStatement
    {
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st;
    }

    public function one(string $sql, array $params = []): ?array
    {
        $row = $this->run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public function all(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll();
    }

    public function val(string $sql, array $params = [])
    {
        $v = $this->run($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    public function lastId(): string
    {
        return $this->pdo->lastInsertId();
    }

    public function tableExists(string $name): bool
    {
        $row = $this->one(
            'SELECT COUNT(*) AS c FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ?',
            [$name]
        );
        return $row && (int) $row['c'] > 0;
    }

    public function columnExists(string $table, string $col): bool
    {
        $row = $this->one(
            'SELECT COUNT(*) AS c FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
            [$table, $col]
        );
        return $row && (int) $row['c'] > 0;
    }
}
