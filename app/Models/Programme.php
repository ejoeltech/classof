<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Programme
{
    /** @return array<int, array<string, mixed>> */
    public static function all(): array
    {
        return Database::fetchAll(
            'SELECT * FROM programmes WHERE status = "verified" ORDER BY name'
        );
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM programmes WHERE id = ? LIMIT 1', [$id]);
    }

    /** @return array<string, mixed>|null */
    public static function findByKey(string $name): ?array
    {
        return Database::fetch(
            'SELECT * FROM programmes WHERE name_key = ? LIMIT 1',
            [Institution::normalizeKey($name)]
        );
    }

    public static function create(string $name, ?string $field = null, ?string $level = null, string $source = 'national list'): int
    {
        $key = Institution::normalizeKey($name);
        $existing = Database::fetch('SELECT id FROM programmes WHERE name_key = ? LIMIT 1', [$key]);

        if ($existing !== null) {
            return (int) $existing['id'];
        }

        return Database::insert('programmes', [
            'department_id' => null,
            'name' => trim($name),
            'name_key' => $key,
            'level' => $level,
            'field' => $field,
            'source' => $source,
            'status' => 'verified',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
