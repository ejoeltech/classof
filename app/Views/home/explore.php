<?php $title = 'Explore alumni groups'; ?>

<section class="page-hero-sm">
    <div class="container">
        <h1>Find your people.</h1>
        <p>Browse alumni groups for primary schools, secondary schools and universities. Request to join the one that feels like home.</p>
        <form method="get" action="<?= url('explore') ?>" class="explore-bar">
            <div class="search-field">
                <?= icon('search', 17) ?>
                <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search by group or institution…">
            </div>
            <select name="type" class="select" style="max-width:190px">
                <option value="">All institution types</option>
                <?php foreach (App\Models\Group::TYPES as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $type === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="scope" class="select" style="max-width:210px">
                <option value="">All affiliations</option>
                <?php foreach ($scopes as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $scope === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if ($search !== '' || $type !== null || $scope !== null): ?>
                <a href="<?= url('explore') ?>" class="btn btn-ghost">Clear</a>
            <?php endif; ?>
        </form>
    </div>
</section>

<section class="section" style="padding-top:40px">
    <div class="container">
        <p class="muted small mb-2"><?= count($groups) ?> <?= count($groups) === 1 ? 'group' : 'groups' ?> found</p>

        <?php if ($groups === []): ?>
            <div class="card">
                <div class="empty-state">
                    <span class="empty-icon"><?= icon('search', 22) ?></span>
                    <h3>No groups match that search</h3>
                    <p>Try a different institution name, or create the group yourself and invite your classmates.</p>
                    <a href="<?= url('register') ?>" class="btn btn-primary btn-sm mt-2">Get started</a>
                </div>
            </div>
        <?php else: ?>
            <div class="group-preview-grid">
                <?php foreach ($groups as $group): ?>
                    <a class="group-preview" href="<?= url('join/' . $group['slug']) ?>">
                        <div class="group-preview-cover" style="--cover: <?= e($group['cover_color'] ?: '#0b6e4f') ?>"></div>
                        <div class="group-preview-body">
                            <div class="group-preview-logo"><?= e(mb_substr((string) $group['name'], 0, 1)) ?></div>
                            <h3><?= e($group['name']) ?></h3>
                            <p><?= e(App\Models\Group::scopeLabel($group['scope'] ?? 'class')) ?> · <?= e(App\Models\Group::descriptor($group)) ?></p>
                            <div class="group-preview-meta">
                                <span><?= icon('users', 14) ?> <?= (int) $group['member_count'] ?> members</span>
                                <span class="badge badge-neutral"><?= e($group['country'] ?: 'Global') ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
