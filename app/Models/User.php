<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class User
{
    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM users WHERE id = ? LIMIT 1', [$id]);
    }

    /** @return array<string, mixed>|null */
    public static function findByEmail(string $email): ?array
    {
        return Database::fetch('SELECT * FROM users WHERE email = ? LIMIT 1', [strtolower($email)]);
    }

    /** @param array<string, mixed> $data */
    public static function create(array $data): int
    {
        return Database::insert('users', [
            'name' => $data['name'],
            'email' => strtolower((string) $data['email']),
            'password_hash' => password_hash((string) $data['password'], PASSWORD_DEFAULT),
            'phone' => $data['phone'] ?? null,
            'platform_role' => $data['platform_role'] ?? 'user',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** @param array<string, mixed> $data */
    public static function update(array $data, array $where): int
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        return Database::update('users', $data, $where);
    }

    public static function updatePassword(int $id, string $password): void
    {
        Database::update('users', [
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    public static function touchLogin(int $id): void
    {
        Database::update('users', [
            'last_login_at' => date('Y-m-d H:i:s'),
            'remember_token' => null,
        ], ['id' => $id]);
    }

    public static function touchSeen(int $id): void
    {
        Database::update('users', [
            'last_seen_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    public static function lastSeen(int $id): ?string
    {
        $value = Database::value('SELECT last_seen_at FROM users WHERE id = ?', [$id]);

        return $value === false ? null : ($value !== null ? (string) $value : null);
    }

    /** @return array<int, array<string, mixed>> */
    public static function search(string $term, int $limit = 30): array
    {
        $like = '%' . $term . '%';

        return Database::fetchAll(
            'SELECT id, name, email, avatar, occupation, location
             FROM users
             WHERE name LIKE ? OR email LIKE ? OR occupation LIKE ?
             ORDER BY name
             LIMIT ' . $limit,
            [$like, $like, $like]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public static function paginate(int $page = 1, int $perPage = 20, string $search = ''): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $sql = 'SELECT * FROM users';
        $params = [];

        if ($search !== '') {
            $sql .= ' WHERE name LIKE ? OR email LIKE ?';
            $params = ['%' . $search . '%', '%' . $search . '%'];
        }

        $sql .= ' ORDER BY created_at DESC LIMIT ' . $perPage . ' OFFSET ' . $offset;

        return Database::fetchAll($sql, $params);
    }

    public static function count(string $search = ''): int
    {
        if ($search === '') {
            return (int) Database::value('SELECT COUNT(*) FROM users', [], 0);
        }

        return (int) Database::value(
            'SELECT COUNT(*) FROM users WHERE name LIKE ? OR email LIKE ?',
            ['%' . $search . '%', '%' . $search . '%'],
            0
        );
    }
}
