<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Models\NotificationDelivery;
use App\Services\WhatsAppService;

final class WhatsAppController extends Controller
{
    public function verify(): void
    {
        $mode = $_GET['hub_mode'] ?? $_GET['hub.mode'] ?? null;
        $token = $_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? null;
        $challenge = $_GET['hub_challenge'] ?? $_GET['hub.challenge'] ?? '';

        $expected = (string) config('whatsapp.webhook_verify_token');

        if ($mode === 'subscribe' && $expected !== '' && hash_equals($expected, (string) $token)) {
            header('Content-Type: text/plain');
            echo $challenge;
            exit;
        }

        Response::abort(403, 'Invalid WhatsApp verify token.');
    }

    public function receive(): void
    {
        $payload = (string) file_get_contents('php://input');
        $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? null;

        if (!WhatsAppService::verifyWebhookSignature($payload, $signature)) {
            Response::json(['status' => 'invalid signature'], 401);
        }

        $event = json_decode($payload, true);

        if (!is_array($event)) {
            Response::json(['status' => 'ok']);
        }

        foreach ((array) ($event['entry'] ?? []) as $entry) {
            foreach ((array) ($entry['changes'] ?? []) as $change) {
                foreach ((array) ($change['value']['statuses'] ?? []) as $status) {
                    $providerId = $status['id'] ?? null;
                    $state = $status['status'] ?? null;

                    if (!is_string($providerId) || !is_string($state)) {
                        continue;
                    }

                    $delivery = NotificationDelivery::findByProviderId($providerId);

                    if ($delivery !== null) {
                        NotificationDelivery::markProviderStatus((int) $delivery['id'], $state);
                    }
                }
            }
        }

        Response::json(['status' => 'ok']);
    }
}
