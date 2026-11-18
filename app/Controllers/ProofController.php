<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Guard;
use App\Core\Response;

final class ProofController extends Controller
{
    /**
     * Stream a private membership-proof document to its owner or a group manager.
     */
    public function show(string $file): void
    {
        $filename = basename($file);
        $membership = Database::fetch(
            'SELECT * FROM memberships WHERE proof_file = ? LIMIT 1',
            [$filename]
        );

        if ($membership === null) {
            Response::abort(404, 'That document could not be found.');
        }

        $userId = (int) user()['id'];
        $isOwner = (int) $membership['user_id'] === $userId;

        $viewerMembership = Guard::membership((int) $membership['group_id']);
        $isManager = Guard::canManage($viewerMembership);

        if (!$isOwner && !$isManager) {
            Response::abort(403, 'You are not allowed to view this document.');
        }

        $path = rtrim((string) config('uploads.proofs_path'), '/\\') . DIRECTORY_SEPARATOR . $filename;

        if (!is_file($path)) {
            Response::abort(404, 'That document is no longer available.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

        if (!in_array($mime, $allowed, true)) {
            $mime = 'application/octet-stream';
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($path));
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store, max-age=0');
        readfile($path);
        exit;
    }
}
