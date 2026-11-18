<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function redirect(string $path, int $status = 302): never
    {
        $base = defined('BASE_URL_PATH') ? BASE_URL_PATH : '';

        if (preg_match('#^https?://#i', $path)) {
            $location = $path;
        } elseif ($base !== '' && ($path === $base || str_starts_with($path, $base . '/'))) {
            // Already prefixed with the application base path (e.g. "/classof/payments/1").
            $location = $path;
        } else {
            $location = url($path);
        }

        header('Location: ' . $location, true, $status);
        exit;
    }

    public static function back(): never
    {
        self::redirect(Request::previousUrl());
    }

    public static function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function abort(int $status, string $message = ''): never
    {
        http_response_code($status);

        $messages = [
            403 => 'You do not have permission to access this page.',
            404 => 'The page you are looking for could not be found.',
            419 => 'Your session expired. Please try again.',
            429 => 'Too many attempts. Please try again later.',
        ];

        $message = $message !== '' ? $message : ($messages[$status] ?? 'Something went wrong.');

        $view = BASE_PATH . '/app/Views/errors/error.php';
        if (is_file($view)) {
            $code = $status;
            require $view;
        } else {
            echo '<h1>' . $code . '</h1><p>' . e($message) . '</p>';
        }

        exit;
    }
}
