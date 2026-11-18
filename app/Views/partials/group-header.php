<?php
$basePath = '/groups/' . $group['slug'];
$path = current_path();
$sub = substr($path, strlen($basePath));
$sub = $sub === '' ? '' : trim($sub, '/');
$firstSegment = explode('/', $sub)[0] ?? '';

$isManager = is_manager($membership);
$isActiveMember = ($membership['status'] ?? '') === 'active';
$memberCount = App\Models\Membership::countActive((int) $group['id']);
$hasCoop = App\Models\Cooperative::forGroup((int) $group['id']) !== null;

$tabs = [
    ['key' => '', 'label' => 'Overview', 'icon' => 'grid', 'href' => $basePath, 'show' => true],
    ['key' => 'members', 'label' => 'Members', 'icon' => 'users', 'href' => $basePath . '/members', 'show' => true],
    ['key' => 'announcements', 'label' => 'Announcements', 'icon' => 'megaphone', 'href' => $basePath . '/announcements', 'show' => true],
    ['key' => 'events', 'label' => 'Events', 'icon' => 'calendar', 'href' => $basePath . '/events', 'show' => true],
    ['key' => 'dues', 'label' => 'Dues', 'icon' => 'wallet', 'href' => $basePath . '/dues', 'show' => true],
    ['key' => 'welfare', 'label' => 'Welfare', 'icon' => 'heart', 'href' => $basePath . '/welfare', 'show' => true],
    ['key' => 'elections', 'label' => 'Elections', 'icon' => 'vote', 'href' => $basePath . '/elections', 'show' => true],
    ['key' => 'coop', 'label' => 'Co-op', 'icon' => 'card', 'href' => $basePath . '/coop', 'show' => $hasCoop || $isManager],
    ['key' => 'treasury', 'label' => 'Treasury', 'icon' => 'chart', 'href' => $basePath . '/treasury', 'show' => $isManager],
    ['key' => 'site', 'label' => 'Website', 'icon' => 'link', 'href' => $basePath . '/site', 'show' => $isManager],
    ['key' => 'settings', 'label' => 'Settings', 'icon' => 'settings', 'href' => $basePath . '/settings', 'show' => $isManager],
];
?>
<section class="group-hero" style="--cover: <?= e($group['cover_color'] ?: '#0b6e4f') ?>">
    <div class="group-cover"></div>
    <div class="group-hero-body">
        <div class="group-logo">
            <?php if (!empty($group['logo'])): ?>
                <img src="<?= e(upload_url($group['logo'])) ?>" alt="<?= e($group['name']) ?>">
            <?php else: ?>
                <?= e(mb_substr((string) $group['name'], 0, 1)) ?>
            <?php endif; ?>
        </div>
        <div class="group-hero-text">
            <div class="group-kicker">
                <span class="badge badge-gold"><?= e(App\Models\Group::scopeLabel($group['scope'] ?? 'class')) ?></span>
                <span class="group-kicker-item"><?= e(App\Models\Group::typeLabel($group['type'])) ?></span>
                <span class="group-kicker-item">· <?= e(App\Models\Group::descriptor($group)) ?></span>
                <?php if (!empty($group['institution'])): ?>
                    <span class="group-kicker-item">· <?= e($group['institution']) ?></span>
                <?php endif; ?>
            </div>
            <h1><?= e($group['name']) ?></h1>
            <p class="group-sub">
                <?= icon('users', 15) ?> <?= $memberCount ?> <?= $memberCount === 1 ? 'member' : 'members' ?>
                <?php if (!empty($group['country'])): ?>
                    <span class="dot-sep">·</span> <?= icon('location', 15) ?> <?= e($group['country']) ?>
                <?php endif; ?>
                <span class="dot-sep">·</span>
                <span class="badge badge-<?= $group['join_policy'] === 'invite' ? 'warn' : 'info' ?>">
                    <?= $group['join_policy'] === 'invite' ? 'Invite only' : 'Proof + approval' ?>
                </span>
            </p>
        </div>
        <?php if ($isManager): ?>
            <div class="group-hero-side">
                <a href="<?= url($basePath . '/members/invite') ?>" class="btn btn-outline btn-sm"><?= icon('plus', 15) ?> Invite</a>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($isActiveMember): ?>
        <nav class="tabs" aria-label="Group sections">
            <?php foreach ($tabs as $tab): ?>
                <?php if (!$tab['show']) { continue; } ?>
                <a href="<?= url($tab['href']) ?>"
                   class="tab <?= $firstSegment === $tab['key'] ? 'is-active' : '' ?>">
                    <?= icon($tab['icon'], 16) ?> <span><?= e($tab['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>
</section>
