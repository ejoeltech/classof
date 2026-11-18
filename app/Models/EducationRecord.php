<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class EducationRecord
{
    /** @return array<int, array<string, mixed>> */
    public static function forUser(int $userId): array
    {
        return Database::fetchAll(
            'SELECT e.*, i.name AS institution_name, i.type AS institution_type,
                    i.ownership AS institution_ownership, i.state AS institution_state, i.status AS institution_status,
                    f.name AS faculty_name, d.name AS department_name, p.name AS programme_name
             FROM education_records e
             LEFT JOIN institutions i ON i.id = e.institution_id
             LEFT JOIN faculties f ON f.id = e.faculty_id
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN programmes p ON p.id = e.programme_id
             WHERE e.user_id = ?
             ORDER BY e.graduation_year DESC, e.created_at DESC',
            [$userId]
        );
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::fetch(
            'SELECT e.*, i.name AS institution_name, i.status AS institution_status
             FROM education_records e
             LEFT JOIN institutions i ON i.id = e.institution_id
             WHERE e.id = ?
             LIMIT 1',
            [$id]
        );
    }

    /** @param array<string, mixed> $data */
    public static function create(array $data): int
    {
        return Database::insert('education_records', [
            'user_id' => $data['user_id'],
            'institution_id' => $data['institution_id'] ?? null,
            'institution_name_raw' => $data['institution_name_raw'] ?? null,
            'programme' => $data['programme'] ?? null,
            'faculty' => $data['faculty'] ?? null,
            'department' => $data['department'] ?? null,
            'faculty_id' => $data['faculty_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'programme_id' => $data['programme_id'] ?? null,
            'graduation_year' => $data['graduation_year'] ?? null,
            'status' => 'unverified',
            'source' => $data['source'] ?? 'profile',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function delete(int $id): void
    {
        Database::delete('education_records', ['id' => $id]);
    }

    public static function countForUser(int $userId): int
    {
        return (int) Database::value('SELECT COUNT(*) FROM education_records WHERE user_id = ?', [$userId], 0);
    }

    /** @return array<int, array<string, mixed>> */
    public static function withVerifiedInstitution(int $userId): array
    {
        return Database::fetchAll(
            'SELECT e.*, i.name AS institution_name
             FROM education_records e
             INNER JOIN institutions i ON i.id = e.institution_id
             WHERE e.user_id = ? AND i.status = "verified" AND e.status <> "rejected"
             ORDER BY e.graduation_year DESC',
            [$userId]
        );
    }
}
