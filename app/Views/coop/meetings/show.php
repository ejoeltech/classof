<?php
$title = $meeting['title'];
$membership = $groupMembership ?? null;
require BASE_PATH . '/app/Views/partials/group-header.php';
$present = count(array_filter($attendance, static fn (array $a): bool => $a['status'] === 'present'));
$absent = count(array_filter($attendance, static fn (array $a): bool => $a['status'] === 'absent'));
$rollCount = count($rollCall);
?>
<div class="page-head">
    <div>
        <a href="<?= url('groups/' . $group['slug'] . '/coop/meetings') ?>" class="tiny muted" style="display:inline-flex;align-items:center;gap:4px;margin-bottom:6px"><?= icon('chevron-right', 13) ?> All meetings</a>
        <h1><?= e($meeting['title']) ?></h1>
        <p><?= e(date_time($meeting['scheduled_at'])) ?><?= !empty($meeting['location']) ? ' · ' . e($meeting['location']) : '' ?></p>
    </div>
    <span class="badge badge-<?= $meeting['status'] === 'completed' ? 'success' : ($meeting['status'] === 'cancelled' ? 'danger' : 'info') ?>"><?= e(ucfirst($meeting['status'])) ?></span>
</div>

<div class="stat-grid mb-3" style="grid-template-columns:repeat(3,1fr)">
    <div class="stat-card"><span class="stat-icon"><?= icon('users', 20) ?></span><div class="stat-meta"><span class="stat-label">Roll call</span><span class="stat-value"><?= $rollCount ?></span><span class="stat-hint">Active members</span></div></div>
    <div class="stat-card"><span class="stat-icon"><?= icon('check', 20) ?></span><div class="stat-meta"><span class="stat-label">Present</span><span class="stat-value"><?= $present ?></span></div></div>
    <div class="stat-card"><span class="stat-icon warn"><?= icon('alert', 20) ?></span><div class="stat-meta"><span class="stat-label">Absent</span><span class="stat-value"><?= $absent ?></span><span class="stat-hint">Fine <?= money($coop['fine_absent'], $group['currency']) ?> each</span></div></div>
</div>

<div class="split-main">
    <div class="stack">
        <?php if (!empty($meeting['agenda'])): ?>
            <section class="card">
                <div class="card-header"><h2>Agenda</h2></div>
                <div class="card-body"><div class="prose"><?= nl2br(e($meeting['agenda'])) ?></div></div>
            </section>
        <?php endif; ?>

        <section class="card">
            <div class="card-header"><h2>Attendance</h2><span class="badge badge-neutral"><?= count($attendance) ?> recorded</span></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Member</th><th>Status</th><th>Note</th></tr></thead>
                    <tbody>
                    <?php if ($attendance === []): ?>
                        <tr><td colspan="3" class="empty-row">No attendance recorded yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($attendance as $row): ?>
                        <tr>
                            <td>
                                <div class="table-person">
                                    <?= avatar($row['name'], $row['avatar'] ?? null, 30) ?>
                                    <div class="table-person-meta"><strong><?= e($row['name']) ?></strong><span><?= e($row['member_no']) ?></span></div>
                                </div>
                            </td>
                            <td><span class="badge badge-<?= $row['status'] === 'present' ? 'success' : ($row['status'] === 'absent' ? 'danger' : ($row['status'] === 'apology' ? 'warn' : 'neutral')) ?>"><?= e(ucfirst($row['status'])) ?></span></td>
                            <td class="small muted"><?= e($row['note'] ?: '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <?php if (!empty($meeting['minutes'])): ?>
            <section class="card">
                <div class="card-header"><h2>Minutes</h2></div>
                <div class="card-body"><div class="prose"><?= nl2br(e($meeting['minutes'])) ?></div></div>
            </section>
        <?php endif; ?>
    </div>

    <div class="stack">
        <?php if ($isOfficer): ?>
            <section class="card">
                <div class="card-header"><h2>Record attendance</h2></div>
                <div class="card-body">
                    <form method="post" action="<?= url('groups/' . $group['slug'] . '/coop/meetings/' . $meeting['id'] . '/attendance') ?>" data-submit-once>
                        <?= csrf_field() ?>
                        <div class="stack-sm">
                            <?php foreach ($rollCall as $row): ?>
                                <div class="field">
                                    <label style="display:flex;justify-content:space-between;align-items:center;gap:8px">
                                        <span><?= e($row['name']) ?></span>
                                    </label>
                                    <div class="flex" style="gap:6px">
                                        <select name="status[<?= (int) $row['member_id'] ?>]" class="select" style="flex:1">
                                            <?php foreach (['present' => 'Present', 'absent' => 'Absent', 'apology' => 'Apology', 'excused' => 'Excused'] as $key => $label): ?>
                                                <option value="<?= e($key) ?>" <?= ($row['status'] ?? 'present') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="text" name="note[<?= (int) $row['member_id'] ?>]" value="<?= e($row['note'] ?? '') ?>" placeholder="Note" style="width:110px">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block mt-2">Save attendance</button>
                    </form>

                    <?php if ($absent > 0): ?>
                        <form method="post" action="<?= url('groups/' . $group['slug'] . '/coop/meetings/' . $meeting['id'] . '/fine-absent') ?>" class="mt-2" data-confirm="Issue the absence fine to all members marked absent?">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-outline btn-block">Fine <?= $absent ?> absent member(s)</button>
                        </form>
                    <?php endif; ?>
                </div>
            </section>

            <section class="card">
                <div class="card-header"><h2>Close meeting</h2></div>
                <div class="card-body">
                    <form method="post" action="<?= url('groups/' . $group['slug'] . '/coop/meetings/' . $meeting['id'] . '/complete') ?>" class="stack-sm" data-submit-once>
                        <?= csrf_field() ?>
                        <div class="field">
                            <label for="minutes">Minutes</label>
                            <textarea id="minutes" name="minutes" class="textarea" style="min-height:100px" placeholder="Key decisions and action points…"><?= e($meeting['minutes'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Mark completed</button>
                    </form>
                    <form method="post" action="<?= url('groups/' . $group['slug'] . '/coop/meetings/' . $meeting['id'] . '/cancel') ?>" class="mt-2" data-confirm="Cancel this meeting?">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-danger-ghost btn-block btn-sm">Cancel meeting</button>
                    </form>
                </div>
            </section>
        <?php endif; ?>
    </div>
</div>
