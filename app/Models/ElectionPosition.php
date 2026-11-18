<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class ElectionPosition
{
    /** @return array<int, array<string, mixed>> */
    public static function forElection(int $electionId): array
    {
        return Database::fetchAll(
            'SELECT p.*,
                    (SELECT COUNT(*) FROM election_candidates c WHERE c.position_id = p.id AND c.status = "approved") AS candidate_count
             FROM election_positions p
             WHERE p.election_id = ?
             ORDER BY p.sort_order, p.id',
            [$electionId]
        );
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::fetch(
            'SELECT p.*, e.group_id, e.status AS election_status, e.starts_at, e.ends_at, e.title AS election_title
             FROM election_positions p
             INNER JOIN elections e ON e.id = p.election_id
             WHERE p.id = ?
             LIMIT 1',
            [$id]
        );
    }

    /** @param array<string, mixed> $data */
    public static function create(int $electionId, string $title, ?string $description = null, int $seats = 1, int $sortOrder = 0): int
    {
        return Database::insert('election_positions', [
            'election_id' => $electionId,
            'title' => $title,
            'description' => $description,
            'seats' => max(1, $seats),
            'sort_order' => $sortOrder,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function delete(int $id): void
    {
        Database::delete('election_positions', ['id' => $id]);
    }
}
