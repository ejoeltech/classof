<?php $title = 'Classmates'; ?>
<div class="page-head">
    <div>
        <h1>Classmates</h1>
        <p>People already on Classof with the same confirmed education record. No contact upload, no global search.</p>
    </div>
    <div class="page-head-actions">
        <a href="<?= url('profile/education') ?>" class="btn btn-outline">Education records</a>
        <a href="<?= url('profile') ?>" class="btn btn-ghost">Back to profile</a>
    </div>
</div>

<?php if (empty($classmatesEnabled)): ?>
    <div class="callout info mb-3">
        <?= icon('info', 17) ?>
        <div>Classmate discovery is currently switched off by the platform administrator. Your opt-in preference is kept, but no classmates are shown until it is turned back on.</div>
    </div>
<?php endif; ?>

<?php if (!$optedIn): ?>
    <div class="card" style="max-width:680px">
        <div class="card-body">
            <span class="empty-icon" style="margin-bottom:12px"><?= icon('users', 22) ?></span>
            <h2 style="font-size:20px;margin-bottom:6px">Opt in to classmate discovery</h2>
            <p class="muted">When you opt in, you can see and be seen by members with the same <strong>confirmed</strong> institution and graduation year. Members who do not opt in stay hidden.</p>
            <div class="callout info mt-2">
                <?= icon('shield', 17) ?>
                <div>No phone contacts are uploaded. Classof never searches outside the platform. You can opt out at any time.</div>
            </div>
            <form method="post" action="<?= url('classmates/opt-in') ?>" class="mt-2" data-submit-once>
                <?= csrf_field() ?>
                <input type="hidden" name="classmate_opt_in" value="1">
                <button type="submit" class="btn btn-primary">Opt in and see classmates</button>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="between mb-3" style="flex-wrap:wrap;gap:12px">
        <p class="muted small" style="margin:0"><?= count($classmates) ?> <?= count($classmates) === 1 ? 'classmate' : 'classmates' ?> found from your confirmed records.</p>
        <form method="post" action="<?= url('classmates/opt-in') ?>" data-confirm="Opt out of classmate discovery? You will be hidden from other members.">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-ghost btn-sm">Opt out</button>
        </form>
    </div>

    <?php if ($classmates === []): ?>
        <div class="card">
            <div class="empty-state">
                <span class="empty-icon"><?= icon('users', 22) ?></span>
                <?php if (empty($classmatesEnabled)): ?>
                    <h3>Discovery paused</h3>
                    <p>Classmate discovery is switched off platform-wide. Your education records are unchanged, and classmates will appear here again if an administrator turns it back on.</p>
                <?php else: ?>
                    <h3>No classmates yet</h3>
                    <p>Classmate discovery only uses education records a platform admin has confirmed. Add or correct your records, or check back as more members join.</p>
                    <a href="<?= url('profile/education') ?>" class="btn btn-outline btn-sm mt-2">Update education</a>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="grid-3">
            <?php foreach ($classmates as $classmate): ?>
                <article class="card">
                    <div class="card-body">
                        <div class="flex" style="align-items:flex-start;gap:12px">
                            <?= avatar($classmate['name'], $classmate['avatar'] ?? null, 48) ?>
                            <div style="flex:1;min-width:0">
                                <h3 style="font-size:15px;margin-bottom:2px"><?= e($classmate['name']) ?></h3>
                                <div class="tiny muted"><?= e($classmate['occupation'] ?: 'Occupation not stated') ?></div>
                                <?php if (!empty($classmate['location'])): ?><div class="tiny muted"><?= e($classmate['location']) ?></div><?php endif; ?>
                            </div>
                        </div>
                        <div class="divider"></div>
                        <div class="tiny muted">
                            <div><strong><?= e($classmate['institution_name']) ?></strong> · Class of <?= e($classmate['graduation_year']) ?></div>
                            <?php if (!empty($classmate['faculty'])): ?><div><?= e($classmate['faculty']) ?></div><?php endif; ?>
                            <?php if (!empty($classmate['department'])): ?><div><?= e($classmate['department']) ?></div><?php endif; ?>
                        </div>
                        <?php if ((int) $classmate['shared_groups'] > 0): ?>
                            <span class="badge badge-neutral mt-2"><?= (int) $classmate['shared_groups'] ?> shared group<?= (int) $classmate['shared_groups'] === 1 ? '' : 's' ?></span>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
