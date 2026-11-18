<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Faculty
{
    public static function normalizeKey(string $name): string
    {
        $key = Institution::normalizeKey($name);
        $key = preg_replace('/^(faculty|school|college) of /', '', $key) ?? $key;

        return trim($key);
    }

    /** @return array<int, array<string, mixed>> */
    public static function forInstitution(int $institutionId): array
    {
        return Database::fetchAll(
            'SELECT * FROM faculties WHERE institution_id = ? AND status = "verified" ORDER BY name',
            [$institutionId]
        );
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM faculties WHERE id = ? LIMIT 1', [$id]);
    }

    /** @return array<string, mixed>|null */
    public static function findForInstitution(int $institutionId, string $name): ?array
    {
        return Database::fetch(
            'SELECT * FROM faculties WHERE institution_id = ? AND name_key = ? LIMIT 1',
            [$institutionId, self::normalizeKey($name)]
        );
    }

    public static function create(int $institutionId, string $name, string $source = 'template'): int
    {
        $key = self::normalizeKey($name);
        $existing = Database::fetch(
            'SELECT id FROM faculties WHERE institution_id = ? AND name_key = ? LIMIT 1',
            [$institutionId, $key]
        );

        if ($existing !== null) {
            return (int) $existing['id'];
        }

        return Database::insert('faculties', [
            'institution_id' => $institutionId,
            'name' => trim($name),
            'name_key' => $key,
            'slug' => slugify($name),
            'source' => $source,
            'status' => 'verified',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
