<?php
$title = ($forYou ?? false) ? 'Groups matched to your education' : 'Explore groups';
$myIds = array_map('intval', $myGroups ?? []);
$forYou = $forYou ?? false;
$matches = $matches ?? [];
$matchCount = $matchCount ?? 0;
?>
<div class="page-head">
    <div>
        <h1><?= $forYou ? 'Groups that match your education' : 'Explore alumni groups' ?></h1>
        <p>
            <?php if ($forYou): ?>
                <?= (int) $matchCount ?> unjoined <?= (int) $matchCount === 1 ? 'group matches' : 'groups match' ?> your education records. Request to join; proof and admin approval still apply.
            <?php else: ?>
                Search the public directory, or switch to the groups matched to your education.
            <?php endif; ?>
        </p>
    </div>
    <div class="page-head-actions">
        <?php if ($forYou): ?>
            <a href="<?= url('explore') ?>" class="btn btn-outline">Browse all groups</a>
        <?php else: ?>
            <a href="<?= url('explore?for=you') ?>" class="btn btn-primary"><?= icon('grid', 15) ?> Matched for you</a>
        <?php endif; ?>
        <a href="<?= url('profile/group-requests') ?>" class="btn btn-ghost">Request a group</a>
    </div>
</div>

<?php if (!$forYou): ?>
    <form method="get" action="<?= url('explore') ?>" class="filter-bar">
        <div class="search-field">
            <?= icon('search', 17) ?>
            <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search by group or institution…">
        </div>
        <select name="type" class="select">
            <option value="">All types</option>
            <?php foreach (App\Models\Group::TYPES as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= $type === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="scope" class="select">
            <option value="">All affiliations</option>
            <?php foreach ($scopes as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= $scope === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-outline">Filter</button>
    </form>
<?php endif; ?>

<?php if ($forYou): ?>
    <?php if ($matches === []): ?>
        <div class="card">
            <div class="empty-state">
                <span class="empty-icon"><?= icon('grid', 22) ?></span>
                <h3>No unjoined groups match your education</h3>
                <p>Add or correct your education records, or explore the full directory. Faculty, department and combined-unit matching ships in the next release.</p>
                <div class="flex mt-2" style="justify-content:center;flex-wrap:wrap">
                    <a href="<?= url('profile/education') ?>" class="btn btn-primary btn-sm">Update education</a>
                    <a href="<?= url('explore') ?>" class="btn btn-outline btn-sm">Browse all groups</a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="group-preview-grid">
            <?php foreach ($matches as $group): ?>
                <div class="group-preview" style="display:flex;flex-direction:column">
                    <div class="group-preview-cover" style="--cover: <?= e($group['cover_color'] ?: '#0b6e4f') ?>"></div>
                    <div class="group-preview-body" style="flex:1;display:flex;flex-direction:column">
                        <div class="group-preview-logo"><?= e(mb_substr((string) $group['name'], 0, 1)) ?></div>
                        <h3><?= e($group['name']) ?></h3>
                        <p><?= e(App\Models\Group::scopeLabel($group['scope'] ?? 'class')) ?> · <?= e(App\Models\Group::descriptor($group)) ?></p>
                        <div class="pill-row mb-2">
                            <span class="badge badge-success">Matched</span>
                            <span class="badge badge-neutral"><?= (int) $group['member_count'] ?> members</span>
                        </div>
                        <div class="tiny muted" style="margin-bottom:10px"><?= e($group['match_reason']) ?></div>
                        <a href="<?= url('join/' . $group['slug']) ?>" class="btn btn-primary btn-sm btn-block" style="margin-top:auto">Request to join</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php elseif ($groups === []): ?>
    <div class="card">
        <div class="empty-state">
            <span class="empty-icon"><?= icon('search', 22) ?></span>
            <h3>No groups found</h3>
            <p>Try a different search, or check the groups matched to your education.</p>
            <a href="<?= url('explore?for=you') ?>" class="btn btn-primary btn-sm mt-2">See matched groups</a>
        </div>
    </div>
<?php else: ?>
    <div class="group-preview-grid">
        <?php foreach ($groups as $group): ?>
            <?php $isMine = in_array((int) $group['id'], $myIds, true); ?>
            <a class="group-preview" href="<?= url($isMine ? 'groups/' . $group['slug'] : 'join/' . $group['slug']) ?>">
                <div class="group-preview-cover" style="--cover: <?= e($group['cover_color'] ?: '#0b6e4f') ?>"></div>
                <div class="group-preview-body">
                    <div class="group-preview-logo"><?= e(mb_substr((string) $group['name'], 0, 1)) ?></div>
                    <h3><?= e($group['name']) ?></h3>
                    <p><?= e(App\Models\Group::scopeLabel($group['scope'] ?? 'class')) ?> · <?= e(App\Models\Group::descriptor($group)) ?></p>
                    <div class="group-preview-meta">
                        <span><?= icon('users', 14) ?> <?= (int) $group['member_count'] ?> members</span>
                        <?php if ($isMine): ?>
                            <span class="badge badge-success">Joined</span>
                        <?php else: ?>
                            <span class="badge badge-neutral"><?= e($group['country'] ?: 'Global') ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
