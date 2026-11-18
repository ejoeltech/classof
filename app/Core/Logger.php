<?php

declare(strict_types=1);

namespace App\Core;

final class Logger
{
    private static function write(string $level, string $message): void
    {
        $dir = BASE_PATH . '/storage/logs';

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $line = sprintf("[%s] %s: %s\n", date('Y-m-d H:i:s'), strtoupper($level), $message);
        @file_put_contents($dir . '/app.log', $line, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $message): void
    {
        self::write('info', $message);
    }

    public static function error(string $message): void
    {
        self::write('error', $message);
    }

    public static function mail(string $to, string $subject, string $body): void
    {
        $dir = BASE_PATH . '/storage/logs';

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $entry = sprintf(
            "\n===== %s =====\nTo: %s\nSubject: %s\n\n%s\n",
            date('Y-m-d H:i:s'),
            $to,
            $subject,
            $body
        );

        @file_put_contents($dir . '/mail.log', $entry, FILE_APPEND | LOCK_EX);
    }

    public static function whatsapp(string $message): void
    {
        $dir = BASE_PATH . '/storage/logs';

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        @file_put_contents(
            $dir . '/whatsapp.log',
            sprintf("[%s] %s\n", date('Y-m-d H:i:s'), $message),
            FILE_APPEND | LOCK_EX
        );
    }
}
