<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class Uploader
{
    /**
     * Validate and store an uploaded image in the public uploads folder.
     *
     * @param array{name: string, type: string, tmp_name: string, error: int, size: int} $file
     */
    public static function image(array $file, string $prefix = 'img'): string
    {
        return self::store(
            $file,
            $prefix,
            (string) config('uploads.path'),
            (array) config('uploads.mimes', []),
            (int) config('uploads.max_kb', 4096)
        );
    }

    /**
     * Store a private membership-proof document in storage/proofs. These files
     * are never served directly; ProofController authorises access first.
     *
     * @param array{name: string, type: string, tmp_name: string, error: int, size: int} $file
     */
    public static function proof(array $file): string
    {
        return self::store(
            $file,
            'proof',
            (string) config('uploads.proofs_path'),
            (array) config('uploads.proof_mimes', []),
            (int) config('uploads.proof_max_kb', 5120)
        );
    }

    public static function delete(?string $filename): void
    {
        self::remove((string) config('uploads.path'), $filename);
    }

    public static function deleteProof(?string $filename): void
    {
        self::remove((string) config('uploads.proofs_path'), $filename);
    }

    /**
     * @param array{name: string, type: string, tmp_name: string, error: int, size: int} $file
     * @param array<int, string> $allowedMimes
     */
    private static function store(array $file, string $prefix, string $dir, array $allowedMimes, int $maxKb): string
    {
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('The file could not be uploaded. Please try again.');
        }

        if (($file['size'] ?? 0) > $maxKb * 1024) {
            throw new RuntimeException('The file is too large. Maximum size is ' . $maxKb . 'KB.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';

        if (!in_array($mime, $allowedMimes, true)) {
            throw new RuntimeException('That file type is not allowed.');
        }

        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'application/pdf' => 'pdf',
        ];

        if (!isset($extensions[$mime])) {
            throw new RuntimeException('That file type is not supported.');
        }

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('The upload directory could not be created.');
        }

        $filename = $prefix . '-' . date('Ymd') . '-' . bin2hex(random_bytes(8)) . '.' . $extensions[$mime];

        if (!move_uploaded_file($file['tmp_name'], $dir . DIRECTORY_SEPARATOR . $filename)) {
            throw new RuntimeException('The file could not be saved.');
        }

        return $filename;
    }

    private static function remove(string $dir, ?string $filename): void
    {
        if ($filename === null || $filename === '') {
            return;
        }

        $path = $dir . DIRECTORY_SEPARATOR . basename($filename);

        if (is_file($path)) {
            @unlink($path);
        }
    }
}
