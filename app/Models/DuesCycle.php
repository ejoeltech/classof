<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class DuesCycle
{
    /** @return array<int, array<string, mixed>> */
    public static function forGroup(int $groupId): array
    {
        return Database::fetchAll(
            'SELECT dc.*,
                    (SELECT COUNT(*) FROM payments p WHERE p.dues_cycle_id = dc.id AND p.status = "success") AS paid_members,
                    (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.dues_cycle_id = dc.id AND p.status = "success") AS collected,
                    (SELECT COUNT(*) FROM memberships m WHERE m.group_id = dc.group_id AND m.status = "active") AS active_members
             FROM dues_cycles dc
             WHERE dc.group_id = ?
             ORDER BY dc.due_date DESC, dc.id DESC',
            [$groupId]
        );
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::fetch(
            'SELECT dc.*,
                    (SELECT COUNT(*) FROM payments p WHERE p.dues_cycle_id = dc.id AND p.status = "success") AS paid_members,
                    (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.dues_cycle_id = dc.id AND p.status = "success") AS collected,
                    (SELECT COUNT(*) FROM memberships m WHERE m.group_id = dc.group_id AND m.status = "active") AS active_members,
                    g.name AS group_name, g.slug AS group_slug
             FROM dues_cycles dc
             INNER JOIN `alumni_groups` g ON g.id = dc.group_id
             WHERE dc.id = ?
             LIMIT 1',
            [$id]
        );
    }

    /** @param array<string, mixed> $data */
    public static function create(array $data): int
    {
        return Database::insert('dues_cycles', [
            'group_id' => $data['group_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'NGN',
            'due_date' => $data['due_date'],
            'status' => 'open',
            'created_by' => $data['created_by'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function close(int $id): void
    {
        Database::update('dues_cycles', [
            'status' => 'closed',
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    /** @return array<string, mixed>|null */
    public static function myPayment(int $cycleId, int $userId): ?array
    {
        return Database::fetch(
            'SELECT * FROM payments
             WHERE dues_cycle_id = ? AND user_id = ? AND purpose = "dues" AND status = "success"
             ORDER BY paid_at DESC LIMIT 1',
            [$cycleId, $userId]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public static function payers(int $cycleId): array
    {
        return Database::fetchAll(
            'SELECT p.*, u.name, u.email, u.avatar
             FROM payments p
             INNER JOIN users u ON u.id = p.user_id
             WHERE p.dues_cycle_id = ? AND p.purpose = "dues" AND p.status = "success"
             ORDER BY p.paid_at DESC',
            [$cycleId]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public static function defaulters(int $cycleId, int $groupId): array
    {
        return Database::fetchAll(
            'SELECT m.*, u.name, u.email, u.phone, u.phone_e164, u.avatar
             FROM memberships m
             INNER JOIN users u ON u.id = m.user_id
             WHERE m.group_id = ? AND m.status = "active"
               AND NOT EXISTS (
                   SELECT 1 FROM payments p
                   WHERE p.dues_cycle_id = ? AND p.user_id = m.user_id
                     AND p.purpose = "dues" AND p.status = "success"
               )
             ORDER BY u.name',
            [$groupId, $cycleId]
        );
    }
}
