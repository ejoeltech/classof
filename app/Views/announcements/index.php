<?php
$title = 'Announcements';
require BASE_PATH . '/app/Views/partials/group-header.php';
?>
<div class="page-head">
    <div>
        <h1>Announcements</h1>
        <p>Official updates from the <?= e($group['name']) ?> executive committee.</p>
    </div>
    <?php if ($canPost): ?>
        <button type="button" class="btn btn-primary" data-modal-open="announcement-modal"><?= icon('plus', 16) ?> New announcement</button>
    <?php endif; ?>
</div>

<?php if ($announcements === []): ?>
    <div class="card">
        <div class="empty-state">
            <span class="empty-icon"><?= icon('megaphone', 22) ?></span>
            <h3>No announcements yet</h3>
            <p><?= $canPost ? 'Publish the first update for your classmates.' : 'Group admins will post updates here.' ?></p>
            <?php if ($canPost): ?>
                <button type="button" class="btn btn-primary btn-sm mt-2" data-modal-open="announcement-modal">Post an announcement</button>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="stack">
        <?php foreach ($announcements as $announcement): ?>
            <article class="card">
                <div class="card-body">
                    <div class="between" style="align-items:flex-start">
                        <div>
                            <h2 style="font-size:17px;margin-bottom:6px">
                                <?= e($announcement['title']) ?>
                                <?php if ((int) $announcement['pinned'] === 1): ?>
                                    <span class="badge badge-gold" style="vertical-align:middle"><?= icon('star', 11) ?> Pinned</span>
                                <?php endif; ?>
                            </h2>
                            <div class="meta-row">
                                <?= avatar($announcement['author_name'], $announcement['avatar'], 24) ?>
                                <span><?= e($announcement['author_name']) ?></span>
                                <span class="dot-sep">·</span>
                                <span><?= e(date_time($announcement['created_at'])) ?></span>
                                <span class="dot-sep">·</span>
                                <span><?= e(time_ago($announcement['created_at'])) ?></span>
                            </div>
                        </div>
                        <?php if ($isManager || (int) $announcement['user_id'] === (int) user()['id']): ?>
                            <form method="post" action="<?= url('groups/' . $group['slug'] . '/announcements/' . $announcement['id'] . '/delete') ?>" data-confirm="Delete this announcement?">
                                <?= csrf_field() ?>
                                <button type="submit" class="icon-btn" title="Delete announcement" aria-label="Delete announcement"><?= icon('trash', 16) ?></button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <div class="prose mt-2" style="border-top:1px solid var(--line);padding-top:16px">
                        <?= nl2br(e($announcement['body'])) ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($canPost): ?>
    <div class="modal" id="announcement-modal" role="dialog" aria-modal="true" aria-labelledby="announcement-modal-title">
        <div class="modal-backdrop" data-modal-close></div>
        <div class="modal-panel">
            <form method="post" action="<?= url('groups/' . $group['slug'] . '/announcements') ?>" data-submit-once>
                <?= csrf_field() ?>
                <div class="modal-head">
                    <h2 id="announcement-modal-title">New announcement</h2>
                    <button type="button" class="icon-btn" data-modal-close aria-label="Close"><?= icon('x', 18) ?></button>
                </div>
                <div class="modal-body">
                    <div class="stack-sm">
                        <div class="field">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="title" required placeholder="e.g. 2026 AGM — save the date" value="<?= e(old('title')) ?>">
                        </div>
                        <div class="field">
                            <label for="body">Message</label>
                            <textarea id="body" name="body" class="textarea" required style="min-height:170px" placeholder="Write your update…"><?= e(old('body')) ?></textarea>
                        </div>
                        <label class="checkbox">
                            <input type="checkbox" name="pinned" value="1">
                            <span>Pin this announcement to the top</span>
                        </label>
                    </div>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-primary">Publish announcement</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
