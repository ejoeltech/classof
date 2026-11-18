<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/*
|--------------------------------------------------------------------------
| Global helpers
|--------------------------------------------------------------------------
*/

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? getenv($key);

    return ($value === false || $value === null || $value === '') ? $default : $value;
}

function config(string $key, mixed $default = null): mixed
{
    return Config::get($key, $default);
}

function url(string $path = ''): string
{
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    $base = defined('BASE_URL_PATH') ? BASE_URL_PATH : '';
    $path = ltrim($path, '/');

    return $base . ($path === '' ? '/' : '/' . $path);
}

function absolute_url(string $path = ''): string
{
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    $host = $_SERVER['HTTP_HOST'] ?? null;

    if ($host !== null) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $base = $scheme . '://' . $host . (defined('BASE_URL_PATH') ? BASE_URL_PATH : '');

        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }

    return rtrim((string) config('app.url', 'http://localhost'), '/') . '/' . ltrim($path, '/');
}

/**
 * A wa.me share link. With a phone it opens that chat; without one it opens
 * WhatsApp's contact picker. Nigerian 080… numbers become 23480….
 */
function whatsapp_share_url(?string $phone, string $text): string
{
    $digits = (string) preg_replace('/\D+/', '', (string) $phone);

    if (str_starts_with($digits, '00')) {
        $digits = substr($digits, 2);
    }

    if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
        $digits = '234' . substr($digits, 1);
    }

    $query = '?text=' . rawurlencode($text);

    return $digits !== ''
        ? 'https://wa.me/' . $digits . $query
        : 'https://wa.me/' . $query;
}

/**
 * URL of the main Classof application, even when the current request is on a
 * group website subdomain.
 */
function main_url(string $path = ''): string
{
    $base = rtrim((string) config('app.url', ''), '/');

    if ($base === '') {
        $domain = (string) config('site.domain', '');

        if ($domain !== '') {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $base = $scheme . '://' . $domain . (defined('BASE_URL_PATH') ? BASE_URL_PATH : '');
        }
    }

    if ($base === '') {
        return absolute_url($path);
    }

    return $base . ($path === '' ? '/' : '/' . ltrim($path, '/'));
}

/**
 * Public URL for a group website, using its subdomain when SITE_DOMAIN is set
 * and the local /site/{subdomain} path otherwise.
 */
function group_site_url(?string $subdomain, string $path = ''): string
{
    if ($subdomain === null || $subdomain === '') {
        return main_url('explore');
    }

    $domain = strtolower(trim((string) config('site.domain', ''), '.'));

    if ($domain !== '') {
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $host = (string) preg_replace('/:\d+$/', '', $host);
        $onSiteHost = str_ends_with($host, '.' . $domain);
        $localDomain = $domain === 'localhost'
            || $domain === '127.0.0.1'
            || str_ends_with($domain, '.localhost');

        // Locally, keep links on the working path fallback unless the request
        // is already on a group subdomain. Production domains always use hosts.
        if (!$localDomain || $onSiteHost) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $base = $scheme . '://' . $subdomain . '.' . $domain . (defined('BASE_URL_PATH') ? BASE_URL_PATH : '');

            return rtrim($base, '/') . ($path === '' ? '/' : '/' . ltrim($path, '/'));
        }
    }

    return absolute_url('site/' . rawurlencode($subdomain) . ($path === '' ? '' : '/' . ltrim($path, '/')));
}

/**
 * The group subdomain requested in the current host, or null for the main app.
 */
function site_subdomain_from_host(): ?string
{
    $domain = strtolower(trim((string) config('site.domain', ''), '.'));

    if ($domain === '') {
        return null;
    }

    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $host = (string) preg_replace('/:\d+$/', '', $host);

    if ($host === '' || $host === $domain || !str_ends_with($host, '.' . $domain)) {
        return null;
    }

    $subdomain = substr($host, 0, -strlen('.' . $domain));

    if ($subdomain === '' || str_contains($subdomain, '.')) {
        return null;
    }

    $reserved = (array) config('site.reserved', []);

    return in_array($subdomain, $reserved, true) ? null : $subdomain;
}


function asset(string $path): string
{
    $relative = 'public/assets/' . ltrim($path, '/');
    $full = BASE_PATH . '/' . $relative;

    return url($relative) . (is_file($full) ? '?v=' . filemtime($full) : '');
}

function upload_url(?string $file): ?string
{
    if ($file === null || $file === '') {
        return null;
    }

    return url('public/uploads/' . ltrim($file, '/'));
}

function redirect(string $path, int $status = 302): never
{
    Response::redirect($path, $status);
}

function back(): never
{
    Response::back();
}

function old(string $key, mixed $default = ''): mixed
{
    $old = $GLOBALS['__classof_old_input'] ?? [];

    return $old[$key] ?? $default;
}

function error_for(array $errors, string $key): ?string
{
    return $errors[$key] ?? null;
}

function flash(string $key, mixed $value = null): mixed
{
    if (func_num_args() === 2) {
        Session::flash($key, $value);

        return null;
    }

    return Session::getFlash($key);
}

function user(): ?array
{
    return Auth::user();
}

function auth(): Auth
{
    return new Auth();
}

function is_admin(): bool
{
    return Auth::isPlatformAdmin();
}

function csrf_token(): string
{
    return Csrf::token();
}

function csrf_field(): string
{
    return Csrf::field();
}

function money(mixed $amount, ?string $currency = null): string
{
    $currency = strtoupper($currency ?? (string) config('payments.currency', 'NGN'));
    $amount = (float) $amount;

    $symbols = [
        'NGN' => '₦',
        'USD' => '$',
        'GBP' => '£',
        'EUR' => '€',
        'GHS' => 'GH₵',
        'KES' => 'KSh',
        'ZAR' => 'R',
        'CAD' => 'C$',
        'AUD' => 'A$',
    ];

    $symbol = $symbols[$currency] ?? ($currency . ' ');
    $decimals = fmod($amount, 1.0) === 0.0 ? 0 : 2;

    return $symbol . number_format($amount, $decimals);
}

function short_date(?string $date): string
{
    if ($date === null || $date === '') {
        return '—';
    }

    return date('M j, Y', strtotime($date));
}

function long_date(?string $date): string
{
    if ($date === null || $date === '') {
        return '—';
    }

    return date('D, M j, Y', strtotime($date));
}

function date_time(?string $date): string
{
    if ($date === null || $date === '') {
        return '—';
    }

    return date('M j, Y · g:i A', strtotime($date));
}

function time_input(?string $date): string
{
    if ($date === null || $date === '') {
        return '';
    }

    return date('Y-m-d\TH:i', strtotime($date));
}

function time_ago(?string $date): string
{
    if ($date === null || $date === '') {
        return '—';
    }

    $timestamp = strtotime($date);
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return 'just now';
    }
    if ($diff < 3600) {
        return floor($diff / 60) . 'm ago';
    }
    if ($diff < 86400) {
        return floor($diff / 3600) . 'h ago';
    }
    if ($diff < 604800) {
        return floor($diff / 86400) . 'd ago';
    }

    return date('M j, Y', $timestamp);
}

function initials(?string $name): string
{
    $name = trim((string) $name);

    if ($name === '') {
        return '?';
    }

    $parts = preg_split('/\s+/', $name) ?: [];
    $first = mb_substr($parts[0], 0, 1);
    $last = count($parts) > 1 ? mb_substr((string) end($parts), 0, 1) : '';

    return mb_strtoupper($first . $last);
}

function avatar(?string $name, ?string $avatar = null, int $size = 40): string
{
    $fontSize = max(11, (int) round($size * 0.36));
    $style = 'width:' . $size . 'px;height:' . $size . 'px;font-size:' . $fontSize . 'px';

    if ($avatar !== null && $avatar !== '') {
        return '<img class="avatar" src="' . e(upload_url($avatar)) . '" alt="' . e($name) . '" style="' . $style . '" loading="lazy">';
    }

    return '<span class="avatar avatar-initials" style="' . $style . '" aria-hidden="true">' . e(initials($name)) . '</span>';
}


function str_limit(?string $value, int $limit = 100): string
{
    $value = trim((string) $value);

    if (mb_strlen($value) <= $limit) {
        return $value;
    }

    return mb_substr($value, 0, $limit - 1) . '…';
}

function is_active(string $pattern): string
{
    $path = Request::capture()->path();

    if ($pattern === '/') {
        return $path === '/' ? 'is-active' : '';
    }

    return str_starts_with($path, $pattern) ? 'is-active' : '';
}

function current_path(): string
{
    return Request::capture()->path();
}

/**
 * Minimal inline icon set (stroke style) used throughout the interface.
 */
function icon(string $name, int $size = 18): string
{
    $paths = [
        'home' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
        'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'user' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'megaphone' => '<path d="M3 11l18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
        'wallet' => '<path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/>',
        'heart' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/>',
        'vote' => '<polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
        'chart' => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        'search' => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
        'plus' => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
        'arrow-right' => '<line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>',
        'check' => '<polyline points="20 6 9 17 4 12"/>',
        'x' => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
        'menu' => '<line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'location' => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
        'link' => '<path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/>',
        'message' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
        'trash' => '<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
        'edit' => '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/>',
        'crown' => '<path d="M3 7l4 4 5-7 5 7 4-4v10H3z"/>',
        'card' => '<rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/>',
        'lock' => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'mail' => '<path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><polyline points="22,6 12,13 2,6"/>',
        'chevron-down' => '<polyline points="6 9 12 15 18 9"/>',
        'chevron-right' => '<polyline points="9 18 15 12 9 6"/>',
        'alert' => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',
        'info' => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
        'star' => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>',
        'inbox' => '<polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>',
    ];

    $inner = $paths[$name] ?? $paths['info'];

    return '<svg class="icon" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $inner . '</svg>';
}


function percent(mixed $part, mixed $total): float
{
    $total = (float) $total;

    if ($total <= 0) {
        return 0.0;
    }

    return min(100.0, ((float) $part / $total) * 100);
}

function is_manager(?array $membership): bool
{
    return $membership !== null && in_array($membership['role'] ?? '', ['owner', 'admin'], true);
}

function role_label(string $role): string
{
    return match ($role) {
        'owner' => 'Owner',
        'admin' => 'Admin',
        'exco' => 'Exco',
        default => 'Member',
    };
}

function status_label(string $status): string
{
    return ucfirst(str_replace('_', ' ', $status));
}

function reference_code(string $prefix = 'CLS'): string
{
    return $prefix . '-' . strtoupper(bin2hex(random_bytes(5)));
}

function slugify(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    $value = trim($value, '-');

    return $value === '' ? 'group-' . bin2hex(random_bytes(3)) : $value;
}

function random_token(int $bytes = 32): string
{
    return bin2hex(random_bytes($bytes));
}

function truncate_text(?string $value, int $limit): string
{
    return mb_strlen((string) $value) > $limit ? mb_substr((string) $value, 0, $limit) . '…' : (string) $value;
}

function dd(mixed ...$values): never
{
    echo '<pre style="background:#0b0f0d;color:#d7e5dd;padding:20px;border-radius:8px;overflow:auto">';
    foreach ($values as $value) {
        var_dump($value);
    }
    echo '</pre>';
    exit;
}
