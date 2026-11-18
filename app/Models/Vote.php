<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDOException;

final class Vote
{
    /**
     * Record one vote. Returns false when the member already voted for that position.
     */
    public static function cast(int $electionId, int $positionId, int $candidateId, int $voterId): bool
    {
        try {
            Database::insert('votes', [
                'election_id' => $electionId,
                'position_id' => $positionId,
                'candidate_id' => $candidateId,
                'voter_id' => $voterId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            return true;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return false;
            }

            throw $e;
        }
    }

    /** @return array<int, array<string, mixed>> */
    public static function results(int $electionId): array
    {
        $positions = ElectionPosition::forElection($electionId);
        $results = [];

        foreach ($positions as $position) {
            $candidates = ElectionCandidate::forPosition((int) $position['id']);

            usort($candidates, static function (array $a, array $b): int {
                return ((int) $b['vote_count'] <=> (int) $a['vote_count']) ?: strcmp((string) $a['name'], (string) $b['name']);
            });

            $total = array_sum(array_map(static fn (array $c): int => (int) $c['vote_count'], $candidates));
            $seats = max(1, (int) $position['seats']);
            $boundaryVotes = isset($candidates[$seats - 1]) ? (int) $candidates[$seats - 1]['vote_count'] : null;
            $tieAtBoundary = $boundaryVotes !== null
                && $boundaryVotes > 0
                && isset($candidates[$seats])
                && (int) $candidates[$seats]['vote_count'] === $boundaryVotes;

            foreach ($candidates as $index => &$candidate) {
                $candidate['percent'] = percent((int) $candidate['vote_count'], $total);
                $candidate['is_winner'] = $tieAtBoundary
                    ? ((int) $candidate['vote_count'] >= (int) $boundaryVotes)
                    : ($index < $seats && (int) $candidate['vote_count'] > 0);
            }
            unset($candidate);

            $results[] = [
                'position' => $position,
                'candidates' => $candidates,
                'total_votes' => $total,
                'winner_count' => count(array_filter($candidates, static fn (array $c): bool => $c['is_winner'])),
            ];
        }

        return $results;
    }

    public static function countForElection(int $electionId): int
    {
        return (int) Database::value('SELECT COUNT(*) FROM votes WHERE election_id = ?', [$electionId], 0);
    }
}
