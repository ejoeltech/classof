<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Department
{
    public static function normalizeKey(string $name): string
    {
        $key = Institution::normalizeKey($name);
        $key = preg_replace('/^(department|dept) of /', '', $key) ?? $key;

        return trim($key);
    }

    /** @return array<int, array<string, mixed>> */
    public static function forFaculty(int $facultyId): array
    {
        return Database::fetchAll(
            'SELECT * FROM departments WHERE faculty_id = ? AND status = "verified" ORDER BY name',
            [$facultyId]
        );
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::fetch(
            'SELECT d.*, f.institution_id, f.name AS faculty_name
             FROM departments d
             INNER JOIN faculties f ON f.id = d.faculty_id
             WHERE d.id = ?
             LIMIT 1',
            [$id]
        );
    }

    /** @return array<string, mixed>|null */
    public static function findForFaculty(int $facultyId, string $name): ?array
    {
        return Database::fetch(
            'SELECT * FROM departments WHERE faculty_id = ? AND name_key = ? LIMIT 1',
            [$facultyId, self::normalizeKey($name)]
        );
    }

    public static function create(int $facultyId, string $name, string $source = 'template'): int
    {
        $key = self::normalizeKey($name);
        $existing = Database::fetch(
            'SELECT id FROM departments WHERE faculty_id = ? AND name_key = ? LIMIT 1',
            [$facultyId, $key]
        );

        if ($existing !== null) {
            return (int) $existing['id'];
        }

        return Database::insert('departments', [
            'faculty_id' => $facultyId,
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
