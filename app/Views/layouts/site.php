<?php
/** @var string $content */
$pageTitle = ($site['headline'] ?? '') ?: ($group['name'] ?? 'Alumni group');
$metaTitle = ($group['name'] ?? '') . ' · ' . config('app.name');
$description = trim((string) ($site['tagline'] ?? ''));

if ($description === '') {
    $description = str_limit(strip_tags((string) ($site['about'] ?? $group['description'] ?? '')), 155);
}

$accent = (string) ($site['accent_color'] ?? '#0B6E4F');
$accent = preg_match('/^#[0-9a-fA-F]{6}$/', $accent) === 1 ? $accent : '#0B6E4F';
$canonical = group_site_url((string) ($site['subdomain'] ?? ''));
$heroImage = !empty($site['hero_image']) ? upload_url($site['hero_image']) : null;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($metaTitle) ?></title>
    <meta name="description" content="<?= e($description) ?>">
    <meta name="theme-color" content="<?= e($accent) ?>">
    <link rel="canonical" href="<?= e($canonical) ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($description) ?>">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <?php if ($heroImage !== null): ?><meta property="og:image" content="<?= e($heroImage) ?>"><?php endif; ?>
    <link rel="icon" href="<?= asset('favicon.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= asset('css/site.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/assistant.css') ?>">
    <style>:root { --site-accent: <?= e($accent) ?>; }</style>
</head>
<body class="site-body">
<?= $content ?>
<?php if (App\Models\PlatformSetting::bool('assistant.enabled')): ?>
    <?php
    $assistantEndpoint = url('site/' . $site['subdomain'] . '/chat');
    $assistantKind = 'group';
    $assistantTitle = 'Group assistant';
    $assistantSubtitle = (string) $group['name'];
    $assistantGreeting = 'Hi! Ask me about ' . $group['name'] . ' — joining, events, news, leadership or contact details.';
    $assistantSuggestions = App\Services\AssistantService::groupSuggestions();
    $assistantHistory = App\Services\AssistantService::history('site:' . $site['subdomain']);
    $assistantAccent = $accent;
    require BASE_PATH . '/app/Views/partials/assistant.php';
    ?>
    <script src="<?= asset('js/assistant.js') ?>"></script>
<?php endif; ?>
</body>
</html>
