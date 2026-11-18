<?php
$title = 'Co-op meetings';
$membership = $groupMembership ?? null;
require BASE_PATH . '/app/Views/partials/group-header.php';
$tone = static fn (string $s): string => match ($s) {
    'completed' => 'success',
    'cancelled' => 'danger',
    default => 'info',
};
?>
<div class="page-head">
    <div>
        <h1>Co-op meetings</h1>
        <p>Agendas, attendance, minutes and absence fines.</p>
    </div>
    <?php if ($isOfficer): ?>
        <button type="button" class="btn btn-primary" data-modal-open="meeting-modal"><?= icon('plus', 16) ?> Schedule meeting</button>
    <?php endif; ?>
</div>

<?php if ($meetings === []): ?>
    <div class="card"><div class="empty-state">
        <span class="empty-icon"><?= icon('calendar', 22) ?></span>
        <h3>No meetings scheduled</h3>
        <p><?= $isOfficer ? 'Schedule the next general meeting and record attendance.' : 'The co-op officers will schedule meetings here.' ?></p>
    </div></div>
<?php else: ?>
    <div class="stack">
        <?php foreach ($meetings as $meeting): ?>
            <article class="card">
                <div class="card-body">
                    <div class="between" style="align-items:flex-start;flex-wrap:wrap;gap:12px">
                        <div>
                            <div class="flex" style="gap:8px;flex-wrap:wrap">
                                <h2 style="font-size:17px"><a href="<?= url('groups/' . $group['slug'] . '/coop/meetings/' . $meeting['id']) ?>" style="color:inherit"><?= e($meeting['title']) ?></a></h2>
                                <span class="badge badge-<?= $tone($meeting['status']) ?>"><?= e(ucfirst($meeting['status'])) ?></span>
                            </div>
                            <div class="meta-row mt-1">
                                <span><?= icon('calendar', 15) ?> <?= e(date_time($meeting['scheduled_at'])) ?></span>
                                <?php if (!empty($meeting['location'])): ?><span class="dot-sep">·</span><span><?= icon('location', 15) ?> <?= e($meeting['location']) ?></span><?php endif; ?>
                            </div>
                        </div>
                        <div class="flex">
                            <span class="badge badge-neutral"><?= (int) $meeting['present_count'] ?> present</span>
                            <span class="badge badge-neutral"><?= (int) $meeting['recorded_count'] ?> recorded</span>
                            <a href="<?= url('groups/' . $group['slug'] . '/coop/meetings/' . $meeting['id']) ?>" class="btn btn-outline btn-sm">Open</a>
                        </div>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($isOfficer): ?>
    <div class="modal" id="meeting-modal" role="dialog" aria-modal="true">
        <div class="modal-backdrop" data-modal-close></div>
        <div class="modal-panel">
            <form method="post" action="<?= url('groups/' . $group['slug'] . '/coop/meetings') ?>" data-submit-once>
                <?= csrf_field() ?>
                <div class="modal-head"><h2>Schedule a meeting</h2><button type="button" class="icon-btn" data-modal-close><?= icon('x', 18) ?></button></div>
                <div class="modal-body">
                    <div class="stack-sm">
                        <div class="field">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="title" required placeholder="e.g. Q1 General Meeting">
                        </div>
                        <div class="form-grid">
                            <div class="field">
                                <label for="scheduled_at">Date &amp; time</label>
                                <input type="datetime-local" id="scheduled_at" name="scheduled_at" required>
                            </div>
                            <div class="field">
                                <label for="location">Location</label>
                                <input type="text" id="location" name="location" placeholder="e.g. School hall / Zoom">
                            </div>
                        </div>
                        <div class="field">
                            <label for="agenda">Agenda</label>
                            <textarea id="agenda" name="agenda" class="textarea" style="min-height:100px" placeholder="What will be discussed?"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-primary">Schedule</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
