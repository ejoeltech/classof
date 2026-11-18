<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Election
{
    public const STATUSES = [
        'draft' => 'Draft',
        'open' => 'Voting open',
        'closed' => 'Voting closed',
        'published' => 'Results published',
    ];

    /** @return array<int, array<string, mixed>> */
    public static function openForUser(int $userId, int $limit = 5): array
    {
        return Database::fetchAll(
            'SELECT e.*, g.name AS group_name, g.slug AS group_slug,
                    (SELECT COUNT(DISTINCT v.position_id) FROM votes v WHERE v.election_id = e.id AND v.voter_id = ?) AS my_positions,
                    (SELECT COUNT(*) FROM election_positions p WHERE p.election_id = e.id) AS position_count
             FROM elections e
             INNER JOIN `alumni_groups` g ON g.id = e.group_id
             INNER JOIN memberships m ON m.group_id = e.group_id AND m.user_id = ? AND m.status = "active"
             WHERE e.status = "open" AND e.starts_at <= NOW() AND e.ends_at >= NOW()
             ORDER BY e.ends_at ASC
             LIMIT ' . $limit,
            [$userId, $userId]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public static function forGroup(int $groupId): array
    {
        return Database::fetchAll(
            'SELECT e.*,
                    (SELECT COUNT(*) FROM election_positions p WHERE p.election_id = e.id) AS position_count,
                    (SELECT COUNT(*) FROM votes v WHERE v.election_id = e.id) AS vote_count
             FROM elections e
             WHERE e.group_id = ?
             ORDER BY FIELD(e.status, "open", "draft", "closed", "published"), e.created_at DESC',
            [$groupId]
        );
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::fetch(
            'SELECT e.*, g.name AS group_name, g.slug AS group_slug, u.name AS creator_name
             FROM elections e
             INNER JOIN `alumni_groups` g ON g.id = e.group_id
             LEFT JOIN users u ON u.id = e.created_by
             WHERE e.id = ?
             LIMIT 1',
            [$id]
        );
    }

    /** @param array<string, mixed> $data */
    public static function create(array $data): int
    {
        return Database::insert('elections', [
            'group_id' => $data['group_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'status' => 'draft',
            'created_by' => $data['created_by'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function setStatus(int $id, string $status): void
    {
        Database::update('elections', [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    public static function isLive(array $election): bool
    {
        if (($election['status'] ?? '') !== 'open') {
            return false;
        }

        $now = time();
        $starts = strtotime((string) $election['starts_at']);
        $ends = strtotime((string) $election['ends_at']);

        return $now >= $starts && $now <= $ends;
    }

    public static function hasVoted(int $electionId, int $userId): bool
    {
        return (int) Database::value(
            'SELECT COUNT(*) FROM votes WHERE election_id = ? AND voter_id = ?',
            [$electionId, $userId],
            0
        ) > 0;
    }

    /** @return array<int, int> */
    public static function votedPositionIds(int $electionId, int $userId): array
    {
        $rows = Database::fetchAll(
            'SELECT position_id FROM votes WHERE election_id = ? AND voter_id = ?',
            [$electionId, $userId]
        );

        return array_map(static fn (array $row): int => (int) $row['position_id'], $rows);
    }

    /** @return array{voters: int, eligible: int, percent: float} */
    public static function turnout(int $electionId, int $groupId): array
    {
        $voters = (int) Database::value(
            'SELECT COUNT(DISTINCT voter_id) FROM votes WHERE election_id = ?',
            [$electionId],
            0
        );

        $eligible = Membership::countActive($groupId);

        return [
            'voters' => $voters,
            'eligible' => $eligible,
            'percent' => percent($voters, $eligible),
        ];
    }
}
