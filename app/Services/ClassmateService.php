<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\EducationRecord;
use App\Models\PlatformSetting;
use App\Models\User;

final class ClassmateService
{
    /**
     * Only confirmed education records, only opted-in viewers and targets, and
     * no contact upload or global search.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function suggestions(int $userId, ?int $limit = null): array
    {
        if (!PlatformSetting::bool('classmates.enabled')) {
            return [];
        }

        $viewer = User::find($userId);

        if ($viewer === null || empty($viewer['classmate_opt_in_at'])) {
            return [];
        }

        $records = array_values(array_filter(
            EducationRecord::withVerifiedInstitution($userId),
            static fn (array $record): bool => $record['status'] === 'verified'
        ));

        if ($records === []) {
            return [];
        }

        $seen = [];

        foreach ($records as $record) {
            $sql = 'SELECT DISTINCT u.id, u.name, u.avatar, u.occupation, u.location,
                        e.graduation_year, e.programme, e.faculty, e.department,
                        i.name AS institution_name,
                        (SELECT COUNT(*) FROM memberships m1
                         INNER JOIN memberships m2 ON m2.group_id = m1.group_id
                         WHERE m1.user_id = ? AND m2.user_id = u.id
                           AND m1.status = "active" AND m2.status = "active") AS shared_groups
                    FROM users u
                    INNER JOIN education_records e ON e.user_id = u.id
                    INNER JOIN institutions i ON i.id = e.institution_id
                    WHERE u.id <> ? AND u.status = "active" AND u.classmate_opt_in_at IS NOT NULL
                      AND e.status = "verified" AND i.status = "verified"
                      AND e.institution_id = ? AND e.graduation_year = ?
                    ORDER BY u.name';
            $params = [$userId, $userId, (int) $record['institution_id'], (int) $record['graduation_year']];

            foreach (Database::fetchAll($sql, $params) as $row) {
                $id = (int) $row['id'];

                if (!isset($seen[$id])) {
                    $seen[$id] = $row;
                }
            }
        }

        $rows = array_values($seen);
        usort($rows, static fn (array $a, array $b): int => strcmp((string) $a['name'], (string) $b['name']));

        if ($limit !== null) {
            $rows = array_slice($rows, 0, max(0, $limit));
        }

        return $rows;
    }

    public static function countForUser(int $userId): int
    {
        return count(self::suggestions($userId));
    }
}
