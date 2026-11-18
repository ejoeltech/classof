<?php

declare(strict_types=1);

/**
 * Queue WhatsApp reminders for events starting within the next 24 hours.
 *
 *   php database/whatsapp_queue_reminders.php
 *
 * Run hourly from cron. Idempotency keys prevent duplicate reminders.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found.');
}

define('BASE_PATH', dirname(__DIR__));
define('BASE_URL_PATH', '');
define('APP_START', microtime(true));

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }

    $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require BASE_PATH . '/app/Core/helpers.php';

$config = require BASE_PATH . '/config/config.php';
App\Core\Config::init($config);
date_default_timezone_set('Africa/Lagos');

use App\Services\WhatsAppService;

$queued = WhatsAppService::queueEventReminders();
fwrite(STDOUT, "Queued {$queued} event reminder(s).\n");
