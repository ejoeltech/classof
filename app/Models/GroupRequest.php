<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class GroupRequest
{
    /** @param array<string, mixed> $data */
    public static function create(array $data): int
    {
        return Database::insert('group_requests', [
            'user_id' => $data['user_id'],
            'institution_id' => $data['institution_id'],
            'name' => $data['name'],
            'scope' => $data['scope'],
            'type' => $data['type'],
            'faculty' => $data['faculty'] ?? null,
            'department' => $data['department'] ?? null,
            'faculty_id' => $data['faculty_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'graduation_year' => $data['graduation_year'] ?? null,
            'description' => $data['description'] ?? null,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::fetch(
            'SELECT r.*, i.name AS institution_name, i.type AS institution_type,
                    u.name AS requester_name, u.email AS requester_email,
                    g.slug AS created_group_slug
             FROM group_requests r
             INNER JOIN institutions i ON i.id = r.institution_id
             INNER JOIN users u ON u.id = r.user_id
             LEFT JOIN `alumni_groups` g ON g.id = r.created_group_id
             WHERE r.id = ?
             LIMIT 1',
            [$id]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public static function forUser(int $userId): array
    {
        return Database::fetchAll(
            'SELECT r.*, i.name AS institution_name, g.slug AS created_group_slug
             FROM group_requests r
             INNER JOIN institutions i ON i.id = r.institution_id
             LEFT JOIN `alumni_groups` g ON g.id = r.created_group_id
             WHERE r.user_id = ?
             ORDER BY r.created_at DESC',
            [$userId]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public static function pending(): array
    {
        return Database::fetchAll(
            'SELECT r.*, i.name AS institution_name, u.name AS requester_name, u.email AS requester_email
             FROM group_requests r
             INNER JOIN institutions i ON i.id = r.institution_id
             INNER JOIN users u ON u.id = r.user_id
             WHERE r.status = "pending"
             ORDER BY r.created_at ASC'
        );
    }

    public static function approve(int $id, int $reviewerId, int $groupId): void
    {
        Database::update('group_requests', [
            'status' => 'approved',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => date('Y-m-d H:i:s'),
            'created_group_id' => $groupId,
            'rejection_reason' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    public static function reject(int $id, int $reviewerId, ?string $reason = null): void
    {
        Database::update('group_requests', [
            'status' => 'rejected',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => date('Y-m-d H:i:s'),
            'rejection_reason' => $reason,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    public static function duplicateExists(int $institutionId, string $scope, ?int $graduationYear, ?int $facultyId, ?int $departmentId): bool
    {
        $sql = 'SELECT COUNT(*) FROM group_requests
                WHERE institution_id = ? AND scope = ? AND status = "pending"
                  AND (graduation_year <=> ?) AND (faculty_id <=> ?) AND (department_id <=> ?)';
        $params = [$institutionId, $scope, $graduationYear, $facultyId, $departmentId];

        return (int) Database::value($sql, $params, 0) > 0;
    }
}
