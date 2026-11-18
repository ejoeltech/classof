<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    private string $method;
    private string $path;

    private function __construct(string $method, string $path)
    {
        $this->method = $method;
        $this->path = $path;
    }

    public static function capture(): self
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        // Allow HTML forms to spoof PUT/PATCH/DELETE.
        if ($method === 'POST' && isset($_POST['_method'])) {
            $spoofed = strtoupper((string) $_POST['_method']);
            if (in_array($spoofed, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $spoofed;
            }
        }

        $uri = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        $uri = urldecode($uri !== '' ? $uri : '/');

        if (BASE_URL_PATH !== '' && str_starts_with($uri, BASE_URL_PATH)) {
            $uri = substr($uri, strlen(BASE_URL_PATH));
        }

        $uri = '/' . trim($uri, '/');

        return new self($method, $uri === '//' ? '/' : $uri);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function wantsJson(): bool
    {
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');

        return str_contains($accept, 'application/json')
            || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;

        return is_string($value) ? trim($value) : $value;
    }

    /** @return array<string, mixed> */
    public static function all(): array
    {
        $data = array_merge($_GET, $_POST);
        unset($data['_token'], $data['_method']);

        return $data;
    }

    public static function string(string $key, string $default = ''): string
    {
        $value = self::input($key, $default);

        return is_scalar($value) ? trim((string) $value) : $default;
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = self::input($key);

        return is_numeric($value) ? (int) $value : $default;
    }

    public static function float(string $key, float $default = 0.0): float
    {
        $value = self::input($key);

        return is_numeric($value) ? (float) $value : $default;
    }

    public static function boolean(string $key): bool
    {
        $value = self::input($key);

        return in_array($value, ['1', 1, true, 'true', 'on', 'yes'], true);
    }

    /** @return array{name: string, type: string, tmp_name: string, error: int, size: int}|null */
    public static function file(string $key): ?array
    {
        $file = $_FILES[$key] ?? null;

        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        /** @var array{name: string, type: string, tmp_name: string, error: int, size: int} $file */
        return $file;
    }

    public static function ip(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public static function userAgent(): string
    {
        return substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);
    }

    public static function fullUrl(): string
    {
        return url(ltrim(self::capture()->path(), '/'));
    }

    public static function previousUrl(): string
    {
        return (string) ($_SERVER['HTTP_REFERER'] ?? url('dashboard'));
    }
}
