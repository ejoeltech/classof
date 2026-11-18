<?php

declare(strict_types=1);

/**
 * WhatsApp outbox worker (CLI).
 *
 *   php database/whatsapp_worker.php
 *   php database/whatsapp_worker.php --limit=100
 *
 * Run from cron every minute. With WHATSAPP_DRIVER=log messages are written to
 * storage/logs/whatsapp.log; with Cloud API credentials they are sent to Meta.
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

use App\Models\NotificationDelivery;
use App\Models\PlatformSetting;
use App\Services\WhatsAppService;

if (!PlatformSetting::bool('whatsapp.enabled')) {
    fwrite(STDOUT, "WhatsApp delivery is disabled in admin settings.\n");
    exit(0);
}

$limit = 50;

foreach ($argv as $argument) {
    if (str_starts_with($argument, '--limit=')) {
        $limit = max(1, (int) substr($argument, 8));
    }
}

$deliveries = NotificationDelivery::due($limit);
$sent = 0;
$failed = 0;

foreach ($deliveries as $delivery) {
    if (!NotificationDelivery::claim((int) $delivery['id'])) {
        continue;
    }

    try {
        $providerId = WhatsAppService::send($delivery);
        NotificationDelivery::markSent((int) $delivery['id'], $providerId);
        $sent++;
        fwrite(STDOUT, "Sent delivery #{$delivery['id']} to {$delivery['user_name']}\n");
    } catch (Throwable $e) {
        NotificationDelivery::markFailed((int) $delivery['id'], $e->getMessage());
        $failed++;
        fwrite(STDERR, "Failed delivery #{$delivery['id']}: {$e->getMessage()}\n");
    }
}

fwrite(STDOUT, "Processed " . count($deliveries) . " delivery(ies): {$sent} sent, {$failed} failed.\n");
