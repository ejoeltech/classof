<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class PasswordReset
{
    public static function create(string $email): string
    {
        Database::delete('password_resets', ['email' => strtolower($email)]);

        $token = random_token(32);

        Database::insert('password_resets', [
            'email' => strtolower($email),
            'token' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+60 minutes')),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $token;
    }

    /** @return array<string, mixed>|null */
    public static function findValid(string $token): ?array
    {
        return Database::fetch(
            'SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1',
            [hash('sha256', $token)]
        );
    }

    public static function consume(string $email): void
    {
        Database::delete('password_resets', ['email' => strtolower($email)]);
    }
}
