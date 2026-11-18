<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        if (!Session::has('_csrf_token')) {
            Session::set('_csrf_token', bin2hex(random_bytes(32)));
        }

        return (string) Session::get('_csrf_token');
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(self::token()) . '">';
    }

    public static function validate(?string $token): bool
    {
        $stored = Session::get('_csrf_token');

        return is_string($token) && is_string($stored) && hash_equals($stored, $token);
    }
}
