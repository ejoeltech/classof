<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class ElectionCandidate
{
    /** @return array<int, array<string, mixed>> */
    public static function forPosition(int $positionId): array
    {
        return Database::fetchAll(
            'SELECT c.*, u.name, u.avatar, u.occupation, u.location,
                    (SELECT COUNT(*) FROM votes v WHERE v.candidate_id = c.id) AS vote_count
             FROM election_candidates c
             INNER JOIN users u ON u.id = c.user_id
             WHERE c.position_id = ?
             ORDER BY c.status = "approved" DESC, u.name',
            [$positionId]
        );
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::fetch(
            'SELECT c.*, u.name, u.avatar, p.election_id, p.title AS position_title, e.group_id, e.status AS election_status
             FROM election_candidates c
             INNER JOIN users u ON u.id = c.user_id
             INNER JOIN election_positions p ON p.id = c.position_id
             INNER JOIN elections e ON e.id = p.election_id
             WHERE c.id = ?
             LIMIT 1',
            [$id]
        );
    }

    /** @return array<string, mixed>|null */
    public static function forUserInElection(int $electionId, int $userId): ?array
    {
        return Database::fetch(
            'SELECT c.* FROM election_candidates c
             INNER JOIN election_positions p ON p.id = c.position_id
             WHERE p.election_id = ? AND c.user_id = ? LIMIT 1',
            [$electionId, $userId]
        );
    }

    public static function create(int $positionId, int $userId, string $manifesto = ''): int
    {
        return Database::insert('election_candidates', [
            'position_id' => $positionId,
            'user_id' => $userId,
            'manifesto' => $manifesto,
            'status' => 'approved',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function setStatus(int $id, string $status): void
    {
        Database::update('election_candidates', ['status' => $status], ['id' => $id]);
    }

    public static function delete(int $id): void
    {
        Database::delete('election_candidates', ['id' => $id]);
    }
}
