<?php

declare(strict_types=1);

namespace App\Services\Payments;

use RuntimeException;

abstract class HttpClient
{
    /**
     * @param array<string, string> $headers
     * @return array{status: int, body: string, json: array<string, mixed>|null}
     */
    protected static function request(string $method, string $url, array $headers = [], ?string $body = null): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('The cURL extension is required for online payments.');
        }

        $handle = curl_init($url);
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => array_merge(['Accept: application/json'], $headers),
            CURLOPT_TIMEOUT => 40,
            CURLOPT_SSL_VERIFYPEER => true,
        ];

        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = $body;
        }

        curl_setopt_array($handle, $options);
        $response = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $error = curl_error($handle);
        curl_close($handle);

        if ($response === false) {
            throw new RuntimeException('Payment gateway is unreachable: ' . $error);
        }

        $decoded = json_decode((string) $response, true);

        return [
            'status' => $status,
            'body' => (string) $response,
            'json' => is_array($decoded) ? $decoded : null,
        ];
    }

    /** @param array<string, mixed> $data */
    protected static function form(array $data): string
    {
        $flatten = static function (array $values, string $prefix = '') use (&$flatten): array {
            $result = [];

            foreach ($values as $key => $value) {
                $name = $prefix === '' ? (string) $key : $prefix . '[' . $key . ']';

                if (is_array($value)) {
                    $result += $flatten($value, $name);
                } else {
                    $result[$name] = (string) $value;
                }
            }

            return $result;
        };

        return http_build_query($flatten($data), '', '&');
    }
}
