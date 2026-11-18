<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;

final class Mailer
{
    public static function send(string $to, string $subject, string $body): void
    {
        // The default driver writes to storage/logs/mail.log, which keeps the app
        // fully functional on local/shared hosting without an SMTP account.
        Logger::mail($to, $subject, $body);
    }

    public static function resetLink(string $email, string $link): void
    {
        self::send(
            $email,
            'Reset your Classof password',
            "We received a request to reset your Classof password.\n\n"
            . "Open this link within 60 minutes:\n{$link}\n\n"
            . "If you did not request a reset, you can safely ignore this email."
        );
    }
}
