<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Load .env
|--------------------------------------------------------------------------
| A tiny dotenv parser: KEY=value, quoted values and # comments supported.
*/
$envPath = BASE_PATH . '/.env';

if (is_file($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $key = trim($key);
        $value = trim($value);

        if (strlen($value) > 1) {
            $first = $value[0];
            if (($first === '"' && str_ends_with($value, '"')) || ($first === "'" && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }
        }

        if ($key !== '' && !array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv($key . '=' . $value);
        }
    }
}

return [
    'app' => [
        'name' => env('APP_NAME', 'Classof'),
        'env' => env('APP_ENV', 'production'),
        'debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOL),
        'url' => rtrim((string) env('APP_URL', ''), '/'),
        'key' => env('APP_KEY', ''),
        'timezone' => env('APP_TIMEZONE', 'Africa/Lagos'),
        'currency' => env('PAYMENT_CURRENCY', 'NGN'),
    ],

    'db' => [
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'classof'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
    ],

    'payments' => [
        'gateway' => env('PAYMENT_GATEWAY', 'manual'),
        'currency' => env('PAYMENT_CURRENCY', 'NGN'),
        'manual' => [
            'bank_name' => env('MANUAL_BANK_NAME', 'Classof Alumni Account'),
            'account_number' => env('MANUAL_BANK_ACCOUNT', '0000000000'),
            'account_holder' => env('MANUAL_BANK_HOLDER', 'Classof Alumni Association'),
        ],
        'paystack' => [
            'public_key' => env('PAYSTACK_PUBLIC_KEY', ''),
            'secret_key' => env('PAYSTACK_SECRET_KEY', ''),
        ],
        'stripe' => [
            'publishable_key' => env('STRIPE_PUBLISHABLE_KEY', ''),
            'secret_key' => env('STRIPE_SECRET_KEY', ''),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
        ],
    ],

    'mail' => [
        'driver' => env('MAIL_DRIVER', 'log'),
        'from' => env('MAIL_FROM', 'no-reply@classof.local'),
    ],

    'whatsapp' => [
        'driver' => env('WHATSAPP_DRIVER', 'log'),
        'api_version' => env('WHATSAPP_API_VERSION', 'v20.0'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID', ''),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN', ''),
        'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN', ''),
        'app_secret' => env('WHATSAPP_APP_SECRET', ''),
        'language' => env('WHATSAPP_TEMPLATE_LANGUAGE', 'en_US'),
        'templates' => [
            'announcement' => env('WHATSAPP_TEMPLATE_ANNOUNCEMENT', 'classof_announcement'),
            'event_reminder' => env('WHATSAPP_TEMPLATE_EVENT', 'classof_event_reminder'),
        ],
    ],

    'uploads' => [
        'path' => BASE_PATH . '/public/uploads',
        'url' => '/uploads',
        'max_kb' => 4096,
        'mimes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
        'proofs_path' => BASE_PATH . '/storage/proofs',
        'proof_max_kb' => 5120,
        'proof_mimes' => ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'],
    ],

    'site' => [
        // Set to your root domain (classof.com) to serve group sites on
        // <subdomain>.classof.com. Leave empty to use the /site/{subdomain}
        // preview path only.
        'domain' => strtolower(trim((string) env('SITE_DOMAIN', ''), '.')),
        'reserved' => ['www', 'app', 'admin', 'api', 'mail', 'support', 'static', 'assets', 'blog', 'help', 'status', 'docs', 'classof'],
    ],

    'ai' => [
        // Optional OpenAI-compatible endpoint used for "Draft with AI".
        // Without a key the editor falls back to a structured group-data draft.
        'api_key' => env('AI_API_KEY', ''),
        'base_url' => rtrim((string) env('AI_BASE_URL', 'https://api.openai.com/v1'), '/'),
        'model' => env('AI_MODEL', 'gpt-4o-mini'),
        'timeout' => (int) env('AI_TIMEOUT', 20),
    ],
];
