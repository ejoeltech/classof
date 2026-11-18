<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Membership;

final class Guard
{
    private const RANK = [
        'owner' => 4,
        'admin' => 3,
        'exco' => 2,
        'member' => 1,
    ];

    public static function rank(string $role): int
    {
        return self::RANK[$role] ?? 0;
    }

    /** @return array<string, mixed>|null */
    public static function membership(int $groupId): ?array
    {
        $userId = Auth::id();

        if ($userId === null) {
            return null;
        }

        return Membership::forUser($groupId, $userId);
    }

    /**
     * Ensure the signed-in user is an active member of the group.
     *
     * @param array<int, string> $roles Allowed roles; empty means any active member.
     * @return array<string, mixed>
     */
    public static function requireMember(int $groupId, array $roles = []): array
    {
        Auth::require();

        $membership = self::membership($groupId);

        if ($membership === null) {
            Response::abort(403, 'You are not a member of this group.');
        }

        if ($membership['status'] === 'pending') {
            Response::abort(403, 'Your request to join this group is still awaiting approval.');
        }

        if ($membership['status'] !== 'active') {
            Response::abort(403, 'Your membership in this group is not active.');
        }

        if ($roles !== [] && !in_array($membership['role'], $roles, true)) {
            Response::abort(403, 'You need a higher role in this group to do that.');
        }

        return $membership;
    }

    /** @return array<string, mixed> */
    public static function requireManager(int $groupId): array
    {
        return self::requireMember($groupId, ['owner', 'admin']);
    }

    public static function canManage(?array $membership): bool
    {
        return $membership !== null
            && in_array($membership['role'] ?? '', ['owner', 'admin'], true)
            && ($membership['status'] ?? '') === 'active';
    }
}
