<?php $title = $group['name']; ?>
<div class="public-group">
    <?php if ($membership !== null && $membership['status'] === 'pending'): ?>
        <div class="alert alert-warn">
            <span class="alert-icon"><?= icon('clock', 18) ?></span>
            <div class="alert-body">Your request to join this group is awaiting approval from a group admin.</div>
        </div>
    <?php endif; ?>

    <div class="public-group-card">
        <div class="public-group-cover" style="--cover: <?= e($group['cover_color'] ?: '#0b6e4f') ?>"></div>
        <div class="public-group-body">
            <div class="public-group-logo"><?= e(mb_substr((string) $group['name'], 0, 1)) ?></div>
            <div class="group-kicker" style="margin-top:12px">
                <span class="badge badge-gold"><?= e(App\Models\Group::scopeLabel($group['scope'] ?? 'class')) ?></span>
                <span class="badge badge-neutral"><?= e(App\Models\Group::typeLabel($group['type'])) ?></span>
                <span><?= e(App\Models\Group::descriptor($group)) ?></span>
                <?php if (!empty($group['country'])): ?><span>· <?= e($group['country']) ?></span><?php endif; ?>
            </div>
            <h1><?= e($group['name']) ?></h1>
            <p class="muted"><?= (int) $memberCount ?> active <?= (int) $memberCount === 1 ? 'member' : 'members' ?></p>

            <?php if (!empty($group['description'])): ?>
                <p class="prose mt-2"><?= nl2br(e($group['description'])) ?></p>
            <?php endif; ?>

            <?php if ($exco !== []): ?>
                <div class="divider"></div>
                <p class="tiny muted" style="text-transform:uppercase;letter-spacing:.08em;font-weight:700">Leadership</p>
                <div class="flex flex-wrap" style="gap:14px">
                    <?php foreach (array_slice($exco, 0, 6) as $leader): ?>
                        <div class="flex" style="gap:8px">
                            <?= avatar($leader['name'], $leader['avatar'] ?? null, 30) ?>
                            <div>
                                <div style="font-size:13px;font-weight:600"><?= e($leader['name']) ?></div>
                                <div class="tiny muted"><?= e($leader['position_title'] ?: role_label($leader['role'])) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="public-group-actions">
                <?php if ($membership !== null && $membership['status'] === 'pending'): ?>
                    <span class="badge badge-warn">Request pending</span>
                <?php elseif ($group['join_policy'] === 'invite'): ?>
                    <div class="callout">
                        <?= icon('lock', 17) ?>
                        <div>This group is invitation only. Ask a group admin to send you an invite link.</div>
                    </div>
                <?php else: ?>
                    <a href="<?= url('join/' . $group['slug']) ?>" class="btn btn-primary">
                        <?= icon('shield', 16) ?> Request to join
                    </a>
                    <span class="muted small">Proof required. A group admin reviews your evidence before you are admitted.</span>
                <?php endif; ?>
                <a href="<?= url('explore') ?>" class="btn btn-ghost">Explore more groups</a>
                <a href="<?= e(whatsapp_share_url(null, 'Join ' . $group['name'] . ' on Classof: ' . absolute_url('join/' . $group['slug']))) ?>" target="_blank" rel="noopener" class="btn btn-ghost">
                    <?= icon('message', 15) ?> Share on WhatsApp
                </a>
                <?php $publicSite = App\Models\GroupSite::findByGroupId((int) $group['id']); ?>
                <?php if ($publicSite !== null && $publicSite['status'] === 'published'): ?>
                    <a href="<?= e(group_site_url((string) $publicSite['subdomain'])) ?>" class="btn btn-ghost" target="_blank" rel="noopener">Visit website</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
