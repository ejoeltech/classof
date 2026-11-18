<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

final class Auth
{
    /** @var array<string, mixed>|null */
    private static ?array $user = null;

    private static bool $loaded = false;

    public static function bootRemembered(): void
    {
        if (Session::has('auth_id')) {
            return;
        }

        $cookie = $_COOKIE['classof_remember'] ?? null;

        if (!is_string($cookie) || !str_contains($cookie, '|')) {
            return;
        }

        [$id, $token] = explode('|', $cookie, 2);

        if (!ctype_digit($id) || $token === '') {
            return;
        }

        $user = User::find((int) $id);

        if ($user === null || empty($user['remember_token'])) {
            return;
        }

        if (!hash_equals((string) $user['remember_token'], hash('sha256', $token))) {
            return;
        }

        Session::set('auth_id', (int) $user['id']);
        Session::regenerate();
    }

    public static function attempt(string $email, string $password, bool $remember = false): bool
    {
        $user = User::findByEmail($email);

        if ($user === null || $user['status'] !== 'active') {
            return false;
        }

        if (!password_verify($password, (string) $user['password_hash'])) {
            return false;
        }

        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            User::updatePassword((int) $user['id'], $password);
        }

        self::login($user, $remember);

        return true;
    }

    /** @param array<string, mixed> $user */
    public static function login(array $user, bool $remember = false): void
    {
        Session::regenerate();
        Session::set('auth_id', (int) $user['id']);
        Session::forget('_csrf_token');

        self::$user = $user;
        self::$loaded = true;

        User::touchLogin((int) $user['id']);

        if ($remember) {
            $token = bin2hex(random_bytes(32));

            User::update(['remember_token' => hash('sha256', $token)], ['id' => (int) $user['id']]);

            setcookie('classof_remember', $user['id'] . '|' . $token, [
                'expires' => time() + 60 * 60 * 24 * 30,
                'path' => BASE_URL_PATH === '' ? '/' : BASE_URL_PATH . '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => (($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off'),
            ]);
        }
    }

    public static function logout(): void
    {
        $id = self::id();

        if ($id !== null) {
            User::update(['remember_token' => null], ['id' => $id]);
        }

        setcookie('classof_remember', '', [
            'expires' => time() - 3600,
            'path' => BASE_URL_PATH === '' ? '/' : BASE_URL_PATH . '/',
        ]);

        Session::destroy();

        self::$user = null;
        self::$loaded = false;
    }

    /** @return array<string, mixed>|null */
    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$user;
        }

        self::$loaded = true;
        $id = Session::get('auth_id');

        if (!is_numeric($id)) {
            return null;
        }

        $user = User::find((int) $id);

        if ($user === null || $user['status'] !== 'active') {
            Session::forget('auth_id');

            return null;
        }

        self::$user = $user;

        return self::$user;
    }

    public static function id(): ?int
    {
        $user = self::user();

        return $user === null ? null : (int) $user['id'];
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function guest(): bool
    {
        return !self::check();
    }

    public static function isPlatformAdmin(): bool
    {
        $user = self::user();

        return $user !== null && ($user['platform_role'] ?? 'user') === 'admin';
    }

    public static function require(): void
    {
        if (!self::check()) {
            Session::set('intended_url', Request::capture()->path());
            Session::flash('error', 'Please sign in to continue.');
            Response::redirect('login');
        }
    }

    public static function requireGuest(): void
    {
        if (self::check()) {
            Response::redirect('dashboard');
        }
    }

    public static function requirePlatformAdmin(): void
    {
        self::require();

        if (!self::isPlatformAdmin()) {
            Response::abort(403);
        }
    }
}
