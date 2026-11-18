<?php
/** @var string $content */
$me = user();
$myGroups = $me !== null ? App\Models\Group::forUser((int) $me['id'], 'active') : [];
$pageTitle = $title ?? 'Dashboard';
$path = current_path();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($pageTitle) ?> · <?= e(config('app.name')) ?></title>
    <link rel="icon" href="<?= asset('favicon.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/assistant.css') ?>">
</head>
<body class="app-body">
<div class="app-shell">
    <aside class="sidebar" id="sidebar" aria-label="Main navigation">
        <div class="sidebar-brand">
            <a href="<?= url('dashboard') ?>" class="brand">
                <span class="brand-mark">C</span>
                <span class="brand-word">classof<span class="brand-dot">.</span></span>
            </a>
            <button type="button" class="icon-btn sidebar-close" data-sidebar-close aria-label="Close menu"><?= icon('x', 18) ?></button>
        </div>

        <div class="sidebar-scroll">
            <p class="sidebar-label">Workspace</p>
            <nav class="sidebar-nav">
                <a href="<?= url('dashboard') ?>" class="nav-item <?= is_active('/dashboard') ?>">
                    <?= icon('grid') ?><span>Dashboard</span>
                </a>
                <a href="<?= url('explore') ?>" class="nav-item <?= is_active('/explore') ?>">
                    <?= icon('search') ?><span>Explore groups</span>
                </a>
                <a href="<?= url('profile') ?>" class="nav-item <?= is_active('/profile') ?>">
                    <?= icon('user') ?><span>My profile</span>
                </a>
                <?php if (App\Models\PlatformSetting::bool('assistant.enabled')): ?>
                    <button type="button" class="nav-item nav-button" data-assistant-open>
                        <?= icon('message') ?><span>Help assistant</span>
                    </button>
                <?php endif; ?>
            </nav>

            <p class="sidebar-label">My groups</p>
            <nav class="sidebar-nav">
                <?php foreach (array_slice($myGroups, 0, 8) as $navGroup): ?>
                    <a href="<?= url('groups/' . $navGroup['slug']) ?>"
                       class="nav-item nav-group <?= str_contains($path, '/groups/' . $navGroup['slug']) ? 'is-active' : '' ?>">
                        <span class="group-dot" style="--dot: <?= e($navGroup['cover_color'] ?: '#0b6e4f') ?>"><?= e(mb_substr((string) $navGroup['name'], 0, 1)) ?></span>
                        <span class="truncate"><?= e($navGroup['name']) ?></span>
                    </a>
                <?php endforeach; ?>

                <?php if ($myGroups === []): ?>
                    <p class="sidebar-empty">You have not joined a group yet.</p>
                <?php endif; ?>

                <a href="<?= url('profile/group-requests') ?>" class="nav-item nav-add <?= is_active('/profile/group-requests') ?>">
                    <?= icon('plus') ?><span>Request a group</span>
                </a>
            </nav>

            <?php if (is_admin()): ?>
                <p class="sidebar-label">Platform</p>
                <nav class="sidebar-nav">
                    <a href="<?= url('admin') ?>" class="nav-item <?= is_active('/admin') ?>">
                        <?= icon('shield') ?><span>Admin console</span>
                    </a>
                </nav>
            <?php endif; ?>
        </div>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <?= avatar($me['name'] ?? '', $me['avatar'] ?? null, 36) ?>
                <div class="sidebar-user-meta">
                    <strong><?= e($me['name'] ?? '') ?></strong>
                    <span><?= e($me['email'] ?? '') ?></span>
                </div>
            </div>
            <form method="post" action="<?= url('logout') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="icon-btn sidebar-logout" title="Sign out" aria-label="Sign out"><?= icon('logout', 17) ?></button>
            </form>
        </div>
    </aside>

    <div class="sidebar-overlay" data-sidebar-close></div>

    <div class="main-area">
        <header class="topbar">
            <div class="topbar-left">
                <button type="button" class="icon-btn menu-btn" data-sidebar-open aria-label="Open menu"><?= icon('menu') ?></button>
                <h1 class="topbar-title"><?= e($pageTitle) ?></h1>
            </div>
            <div class="topbar-actions">
                <a href="<?= url('explore') ?>" class="btn btn-ghost btn-sm hide-sm"><?= icon('search', 16) ?> Explore</a>
                <a href="<?= url('explore?for=you') ?>" class="btn btn-primary btn-sm"><?= icon('search', 16) ?> <span class="hide-xs">Find groups</span></a>
                <div class="user-menu" data-user-menu>
                    <button type="button" class="user-trigger" data-user-toggle aria-haspopup="true" aria-expanded="false">
                        <?= avatar($me['name'] ?? '', $me['avatar'] ?? null, 34) ?>
                        <span class="user-trigger-name hide-sm"><?= e(explode(' ', (string) ($me['name'] ?? 'Member'))[0]) ?></span>
                        <?= icon('chevron-down', 15) ?>
                    </button>
                    <div class="user-dropdown" data-user-dropdown>
                        <div class="dropdown-head">
                            <strong><?= e($me['name'] ?? '') ?></strong>
                            <span><?= e($me['email'] ?? '') ?></span>
                        </div>
                        <a href="<?= url('profile') ?>" class="dropdown-item"><?= icon('user', 16) ?> My profile</a>
                        <a href="<?= url('dashboard') ?>" class="dropdown-item"><?= icon('grid', 16) ?> Dashboard</a>
                        <?php if (is_admin()): ?>
                            <a href="<?= url('admin') ?>" class="dropdown-item"><?= icon('shield', 16) ?> Admin console</a>
                        <?php endif; ?>
                        <form method="post" action="<?= url('logout') ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="dropdown-item dropdown-danger"><?= icon('logout', 16) ?> Sign out</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="content">
            <?php require BASE_PATH . '/app/Views/partials/flash.php'; ?>
            <?= $content ?>
        </main>

        <footer class="app-footer">
            <span>Classof — alumni, welfare &amp; elections in one place.</span>
            <span>&copy; <?= date('Y') ?> <?= e(config('app.name')) ?></span>
        </footer>
    </div>
</div>

<?php if (App\Models\PlatformSetting::bool('assistant.enabled')): ?>
    <?php
    $assistantEndpoint = url('assistant/chat');
    $assistantKind = 'platform';
    $assistantTitle = 'Classof assistant';
    $assistantSubtitle = 'Groups, dues, welfare and more';
    $assistantGreeting = 'Hi ' . explode(' ', (string) ($me['name'] ?? 'there'))[0] . '! Ask me about groups, dues, welfare, elections, the co-op or your account.';
    $assistantSuggestions = App\Services\AssistantService::platformSuggestions();
    $assistantHistory = App\Services\AssistantService::history('app:' . (int) ($me['id'] ?? 0));
    require BASE_PATH . '/app/Views/partials/assistant.php';
    ?>
<?php endif; ?>

<script src="<?= asset('js/app.js') ?>"></script>
<?php if (App\Models\PlatformSetting::bool('assistant.enabled')): ?>
    <script src="<?= asset('js/assistant.js') ?>"></script>
<?php endif; ?>
</body>
</html>
