<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $db = config('db');
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $db['host'],
            $db['port'],
            $db['database']
        );

        try {
            self::$pdo = new PDO($dsn, (string) $db['username'], (string) $db['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException(
                'Database connection failed. Check your .env settings and make sure MySQL is running. ' . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }

        return self::$pdo;
    }

    /** @param array<string|int, mixed> $params */
    public static function run(string $sql, array $params = []): PDOStatement
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute($params);

        return $statement;
    }

    /**
     * @param array<string|int, mixed> $params
     * @return array<string, mixed>|null
     */
    public static function fetch(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @param array<string|int, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /** @param array<string|int, mixed> $params */
    public static function value(string $sql, array $params = [], mixed $default = null): mixed
    {
        $value = self::run($sql, $params)->fetchColumn();

        return $value === false ? $default : $value;
    }

    /** @param array<string, mixed> $data */
    public static function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn (string $column): string => ':' . $column, $columns);

        $sql = sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            $table,
            implode(', ', array_map(static fn (string $c): string => '`' . $c . '`', $columns)),
            implode(', ', $placeholders)
        );

        self::run($sql, $data);

        return (int) self::pdo()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $where
     */
    public static function update(string $table, array $data, array $where): int
    {
        $set = [];
        foreach (array_keys($data) as $column) {
            $set[] = '`' . $column . '` = :set_' . $column;
        }

        $conditions = [];
        foreach (array_keys($where) as $column) {
            $conditions[] = '`' . $column . '` = :where_' . $column;
        }

        $params = [];
        foreach ($data as $column => $value) {
            $params['set_' . $column] = $value;
        }
        foreach ($where as $column => $value) {
            $params['where_' . $column] = $value;
        }

        $sql = sprintf(
            'UPDATE `%s` SET %s WHERE %s',
            $table,
            implode(', ', $set),
            implode(' AND ', $conditions)
        );

        return self::run($sql, $params)->rowCount();
    }

    /** @param array<string, mixed> $where */
    public static function delete(string $table, array $where): int
    {
        $conditions = [];
        $params = [];

        foreach ($where as $column => $value) {
            $conditions[] = '`' . $column . '` = :' . $column;
            $params[$column] = $value;
        }

        $sql = sprintf(
            'DELETE FROM `%s` WHERE %s',
            $table,
            implode(' AND ', $conditions)
        );

        return self::run($sql, $params)->rowCount();
    }

    public static function transaction(callable $callback): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();

        try {
            $result = $callback();
            $pdo->commit();

            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
