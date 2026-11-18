<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class CoopFine
{
    public static function create(array $data): int
    {
        return Database::insert('coop_fines', [
            'cooperative_id' => $data['cooperative_id'],
            'member_id' => $data['member_id'],
            'meeting_id' => $data['meeting_id'] ?? null,
            'reason' => $data['reason'],
            'amount' => $data['amount'],
            'status' => 'unpaid',
            'issued_by' => $data['issued_by'] ?? null,
            'issued_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::fetch(
            'SELECT f.*, u.name AS member_name, u.avatar, cm.user_id, cm.member_no,
                    c.group_id, g.slug AS group_slug
             FROM coop_fines f
             INNER JOIN coop_members cm ON cm.id = f.member_id
             INNER JOIN users u ON u.id = cm.user_id
             INNER JOIN cooperatives c ON c.id = f.cooperative_id
             INNER JOIN `alumni_groups` g ON g.id = c.group_id
             WHERE f.id = ?
             LIMIT 1',
            [$id]
        );
    }

    /**
     * @param array{status?: string, member_id?: int} $filters
     * @return array<int, array<string, mixed>>
     */
    public static function forCoop(int $cooperativeId, array $filters = []): array
    {
        $sql = 'SELECT f.*, u.name AS member_name, u.avatar, cm.member_no, m.title AS meeting_title
                FROM coop_fines f
                INNER JOIN coop_members cm ON cm.id = f.member_id
                INNER JOIN users u ON u.id = cm.user_id
                LEFT JOIN coop_meetings m ON m.id = f.meeting_id
                WHERE f.cooperative_id = ?';
        $params = [$cooperativeId];

        if (!empty($filters['status'])) {
            $sql .= ' AND f.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['member_id'])) {
            $sql .= ' AND f.member_id = ?';
            $params[] = (int) $filters['member_id'];
        }

        $sql .= ' ORDER BY FIELD(f.status, "unpaid", "paid", "waived"), f.issued_at DESC';

        return Database::fetchAll($sql, $params);
    }

    /** @return array<int, array<string, mixed>> */
    public static function forMember(int $memberId): array
    {
        return Database::fetchAll(
            'SELECT f.*, m.title AS meeting_title
             FROM coop_fines f
             LEFT JOIN coop_meetings m ON m.id = f.meeting_id
             WHERE f.member_id = ?
             ORDER BY FIELD(f.status, "unpaid", "paid", "waived"), f.issued_at DESC',
            [$memberId]
        );
    }

    public static function unpaidTotal(int $cooperativeId, ?int $memberId = null): float
    {
        $sql = 'SELECT COALESCE(SUM(amount), 0) FROM coop_fines WHERE cooperative_id = ? AND status = "unpaid"';
        $params = [$cooperativeId];

        if ($memberId !== null) {
            $sql .= ' AND member_id = ?';
            $params[] = $memberId;
        }

        return (float) Database::value($sql, $params, 0);
    }

    public static function markPaid(int $id): void
    {
        Database::update('coop_fines', [
            'status' => 'paid',
            'paid_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    public static function waive(int $id): void
    {
        Database::update('coop_fines', ['status' => 'waived'], ['id' => $id]);
    }

    public static function existsForMeetingMember(int $meetingId, int $memberId): bool
    {
        return (int) Database::value(
            'SELECT COUNT(*) FROM coop_fines WHERE meeting_id = ? AND member_id = ?',
            [$meetingId, $memberId],
            0
        ) > 0;
    }
}
