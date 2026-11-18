<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Membership
{
    public const ROLES = [
        'owner' => 'Owner',
        'admin' => 'Admin',
        'exco' => 'Exco / Committee',
        'member' => 'Member',
    ];

    /**
     * Evidence a member can submit to prove their alumni affiliation.
     */
    public const PROOF_TYPES = [
        'alumni_id' => 'Alumni ID',
        'student_id' => 'Student ID / matriculation card',
        'certificate' => 'Certificate or testimonial',
        'transcript' => 'Transcript / result slip',
        'admission_letter' => 'Admission letter',
        'invitation' => 'Invitation',
        'founder' => 'Group founder',
        'other' => 'Other document',
    ];

    /** Types a member may choose on the public join form. */
    public const JOIN_PROOF_TYPES = ['alumni_id', 'student_id', 'certificate', 'transcript', 'admission_letter', 'other'];

    /** @return array<string, mixed>|null */
    public static function forUser(int $groupId, int $userId): ?array
    {
        return Database::fetch(
            'SELECT * FROM memberships WHERE group_id = ? AND user_id = ? LIMIT 1',
            [$groupId, $userId]
        );
    }

    /** @param array<string, mixed> $data */
    public static function create(array $data): int
    {
        $status = $data['status'] ?? 'pending';
        $now = date('Y-m-d H:i:s');
        $proofType = $data['proof_type'] ?? null;
        $reviewedBy = $data['reviewed_by'] ?? ($status === 'active' ? ($data['approved_by'] ?? null) : null);

        return Database::insert('memberships', [
            'group_id' => $data['group_id'],
            'user_id' => $data['user_id'],
            'role' => $data['role'] ?? 'member',
            'position_title' => $data['position_title'] ?? null,
            'status' => $status,
            'membership_no' => $data['membership_no'] ?? self::nextNumber((int) $data['group_id']),
            'proof_type' => $proofType,
            'proof_reference' => $data['proof_reference'] ?? null,
            'proof_file' => $data['proof_file'] ?? null,
            'proof_note' => $data['proof_note'] ?? null,
            'proof_submitted_at' => $data['proof_submitted_at'] ?? ($proofType !== null ? $now : null),
            'education_record_id' => $data['education_record_id'] ?? null,
            'claim_match' => $data['claim_match'] ?? null,
            'claim_flagged' => !empty($data['claim_flagged']) ? 1 : 0,
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => $data['reviewed_at'] ?? ($status === 'active' && $reviewedBy !== null ? $now : null),
            'rejection_reason' => $data['rejection_reason'] ?? null,
            'joined_at' => $data['joined_at'] ?? ($status === 'active' ? $now : null),
            'approved_by' => $data['approved_by'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public static function nextNumber(int $groupId): string
    {
        $count = (int) Database::value(
            'SELECT COUNT(*) FROM memberships WHERE group_id = ?',
            [$groupId],
            0
        );

        return 'CLS-' . str_pad((string) $groupId, 3, '0', STR_PAD_LEFT) . '-' . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param array{status?: string, role?: string, search?: string} $filters
     * @return array<int, array<string, mixed>>
     */
    public static function list(int $groupId, array $filters = []): array
    {
        $sql = 'SELECT m.*, u.name, u.email, u.phone, u.avatar, u.occupation, u.location
                FROM memberships m
                INNER JOIN users u ON u.id = m.user_id
                WHERE m.group_id = ?';
        $params = [$groupId];

        if (!empty($filters['status'])) {
            $sql .= ' AND m.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['role'])) {
            $sql .= ' AND m.role = ?';
            $params[] = $filters['role'];
        }

        if (!empty($filters['search'])) {
            $sql .= ' AND (u.name LIKE ? OR u.email LIKE ? OR u.occupation LIKE ?)';
            $like = '%' . $filters['search'] . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= ' ORDER BY FIELD(m.role, "owner", "admin", "exco", "member"), u.name';

        return Database::fetchAll($sql, $params);
    }

    public static function approve(int $membershipId, int $approvedBy): void
    {
        $now = date('Y-m-d H:i:s');

        Database::update('memberships', [
            'status' => 'active',
            'joined_at' => $now,
            'approved_by' => $approvedBy,
            'reviewed_by' => $approvedBy,
            'reviewed_at' => $now,
            'rejection_reason' => null,
            'updated_at' => $now,
        ], ['id' => $membershipId]);
    }

    public static function reject(int $membershipId, int $reviewedBy, ?string $reason = null): void
    {
        $now = date('Y-m-d H:i:s');

        Database::update('memberships', [
            'status' => 'rejected',
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => $now,
            'rejection_reason' => $reason,
            'updated_at' => $now,
        ], ['id' => $membershipId]);
    }

    public static function proofState(array $membership): string
    {
        if (($membership['status'] ?? '') === 'rejected') {
            return 'rejected';
        }

        if (($membership['status'] ?? '') === 'active') {
            return !empty($membership['reviewed_at']) || !empty($membership['proof_type']) ? 'verified' : 'none';
        }

        return !empty($membership['proof_submitted_at']) ? 'pending' : 'none';
    }

    public static function setStatus(int $membershipId, string $status): void
    {
        Database::update('memberships', [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $membershipId]);
    }

    public static function setRole(int $membershipId, string $role, ?string $positionTitle = null): void
    {
        Database::update('memberships', [
            'role' => $role,
            'position_title' => $positionTitle,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $membershipId]);
    }

    public static function countActive(int $groupId): int
    {
        return (int) Database::value(
            'SELECT COUNT(*) FROM memberships WHERE group_id = ? AND status = "active"',
            [$groupId],
            0
        );
    }

    /** @return array<int, array<string, mixed>> */
    public static function exco(int $groupId): array
    {
        return Database::fetchAll(
            'SELECT m.*, u.name, u.avatar
             FROM memberships m
             INNER JOIN users u ON u.id = m.user_id
             WHERE m.group_id = ? AND m.status = "active" AND m.role IN ("owner", "admin", "exco")
             ORDER BY FIELD(m.role, "owner", "admin", "exco"), u.name',
            [$groupId]
        );
    }
}
