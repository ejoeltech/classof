<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class WelfareCase
{
    public const CATEGORIES = [
        'bereavement' => 'Bereavement',
        'medical' => 'Medical support',
        'wedding' => 'Wedding',
        'childbirth' => 'Childbirth',
        'education' => 'Education',
        'emergency' => 'Emergency',
        'other' => 'Other',
    ];

    /** @return array<int, array<string, mixed>> */
    public static function forGroup(int $groupId, string $status = ''): array
    {
        $sql = 'SELECT w.*, u.name AS beneficiary_name, u.avatar,
                    COALESCE((SELECT SUM(c.amount) FROM welfare_contributions c WHERE c.welfare_case_id = w.id), 0) AS raised,
                    (SELECT COUNT(DISTINCT c.user_id) FROM welfare_contributions c WHERE c.welfare_case_id = w.id) AS contributors
                FROM welfare_cases w
                INNER JOIN users u ON u.id = w.user_id
                WHERE w.group_id = ?';
        $params = [$groupId];

        if ($status !== '') {
            $sql .= ' AND w.status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY FIELD(w.status, "open", "funded", "closed"), w.created_at DESC';

        return Database::fetchAll($sql, $params);
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::fetch(
            'SELECT w.*, u.name AS beneficiary_name, u.avatar, u.email AS beneficiary_email,
                    g.name AS group_name, g.slug AS group_slug
             FROM welfare_cases w
             INNER JOIN users u ON u.id = w.user_id
             INNER JOIN `alumni_groups` g ON g.id = w.group_id
             WHERE w.id = ?
             LIMIT 1',
            [$id]
        );
    }

    /** @param array<string, mixed> $data */
    public static function create(array $data): int
    {
        return Database::insert('welfare_cases', [
            'group_id' => $data['group_id'],
            'user_id' => $data['user_id'],
            'title' => $data['title'],
            'category' => $data['category'],
            'description' => $data['description'],
            'target_amount' => $data['target_amount'] ?? null,
            'currency' => $data['currency'] ?? 'NGN',
            'status' => 'open',
            'is_anonymous' => !empty($data['is_anonymous']) ? 1 : 0,
            'created_by' => $data['created_by'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function setStatus(int $id, string $status): void
    {
        Database::update('welfare_cases', [
            'status' => $status,
            'closed_at' => $status === 'closed' ? date('Y-m-d H:i:s') : null,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    public static function raised(int $id): float
    {
        return (float) Database::value(
            'SELECT COALESCE(SUM(amount), 0) FROM welfare_contributions WHERE welfare_case_id = ?',
            [$id],
            0
        );
    }

    /** @return array<int, array<string, mixed>> */
    public static function latestForUser(int $userId, int $limit = 5): array
    {
        return Database::fetchAll(
            'SELECT w.*, g.slug AS group_slug, g.name AS group_name,
                    COALESCE((SELECT SUM(c.amount) FROM welfare_contributions c WHERE c.welfare_case_id = w.id), 0) AS raised
             FROM welfare_cases w
             INNER JOIN `alumni_groups` g ON g.id = w.group_id
             INNER JOIN memberships m ON m.group_id = w.group_id AND m.user_id = ? AND m.status = "active"
             WHERE w.status = "open"
             ORDER BY w.created_at DESC
             LIMIT ' . $limit,
            [$userId]
        );
    }

    public static function categoryLabel(string $category): string
    {
        return self::CATEGORIES[$category] ?? ucfirst($category);
    }
}
