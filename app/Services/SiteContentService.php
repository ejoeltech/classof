<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Group;
use App\Models\GroupSite;
use RuntimeException;

final class SiteContentService
{
    public static function aiConfigured(): bool
    {
        return trim((string) config('ai.api_key', '')) !== '';
    }

    /**
     * Produce landing-page copy. Uses the configured AI endpoint when
     * available and always falls back to a structured group-data draft.
     *
     * @param array<string, mixed> $group
     * @return array{headline: string, tagline: string, about: string, source: string}
     */
    public static function draft(array $group, int $memberCount = 0): array
    {
        $fallback = GroupSite::defaultContent($group);

        if (!self::aiConfigured()) {
            return $fallback + ['source' => 'template'];
        }

        try {
            $content = self::request($group, $memberCount);

            return array_merge($fallback, $content, ['source' => 'ai']);
        } catch (\Throwable) {
            return $fallback + ['source' => 'template_fallback'];
        }
    }

    /**
     * @param array<string, mixed> $group
     * @return array{headline: string, tagline: string, about: string}
     */
    private static function request(array $group, int $memberCount): array
    {
        $context = [
            'name' => $group['name'] ?? '',
            'institution' => $group['institution'] ?? '',
            'scope' => Group::scopeLabel($group['scope'] ?? 'class'),
            'affiliation' => Group::descriptor($group),
            'faculty' => $group['faculty'] ?? '',
            'department' => $group['department'] ?? '',
            'graduation_year' => $group['graduation_year'] ?? '',
            'country' => $group['country'] ?? '',
            'description' => $group['description'] ?? '',
            'active_members' => $memberCount,
        ];

        $prompt = "Write concise, welcoming copy for an alumni group's public landing page.\n"
            . "Return only JSON with exactly these keys: headline (max 80 characters), "
            . "tagline (max 140 characters), about (90-130 words).\n"
            . "Use second person, British English, and a warm but factual tone. "
            . "Do not invent achievements, dates, contacts or promises. Do not mention fees, politics or specific people.\n\n"
            . 'Group data: ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $base = rtrim((string) config('ai.base_url', 'https://api.openai.com/v1'), '/');
        $payload = [
            'model' => (string) config('ai.model', 'gpt-4o-mini'),
            'temperature' => 0.6,
            'max_tokens' => 500,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => 'You are a careful alumni communications editor. Reply with valid JSON only.'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ];

        $handle = curl_init($base . '/chat/completions');
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . (string) config('ai.api_key'),
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => max(5, (int) config('ai.timeout', 20)),
        ]);

        $response = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $error = curl_error($handle);
        curl_close($handle);

        if ($response === false) {
            throw new RuntimeException('AI endpoint unreachable: ' . $error);
        }

        if ($status >= 400) {
            throw new RuntimeException('AI endpoint rejected the request with status ' . $status . '.');
        }

        $decoded = json_decode((string) $response, true);
        $content = $decoded['choices'][0]['message']['content'] ?? null;

        if (!is_string($content)) {
            throw new RuntimeException('AI endpoint returned an unexpected response.');
        }

        $content = trim($content);
        $content = (string) preg_replace('/^```(?:json)?\s*|\s*```$/', '', $content);
        $copy = json_decode($content, true);

        if (!is_array($copy)) {
            throw new RuntimeException('AI endpoint did not return JSON copy.');
        }

        $headline = trim((string) ($copy['headline'] ?? ''));
        $tagline = trim((string) ($copy['tagline'] ?? ''));
        $about = trim((string) ($copy['about'] ?? ''));

        if ($headline === '' || $tagline === '' || $about === '') {
            throw new RuntimeException('AI endpoint returned incomplete copy.');
        }

        return [
            'headline' => mb_substr($headline, 0, 160),
            'tagline' => mb_substr($tagline, 0, 255),
            'about' => mb_substr($about, 0, 4000),
        ];
    }
}
