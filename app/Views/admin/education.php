<?php $title = 'Education review'; ?>
<div class="page-head">
    <div>
        <h1>Education review</h1>
        <p>Confirm or reject self-declared education records. Confirmed records unlock the strictest verification policy.</p>
    </div>
    <div class="page-head-actions">
        <a href="<?= url('admin/settings') ?>" class="btn btn-outline">Settings</a>
        <a href="<?= url('admin') ?>" class="btn btn-ghost">Back to console</a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><h2>Awaiting review</h2><span class="badge badge-warn"><?= count($pending) ?></span></div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Member</th><th>Institution</th><th>Unit</th><th>Year</th><th>Submitted</th><th class="text-right">Review</th></tr></thead>
            <tbody>
            <?php if ($pending === []): ?>
                <tr><td colspan="6" class="empty-row">No education records awaiting review.</td></tr>
            <?php endif; ?>
            <?php foreach ($pending as $record): ?>
                <tr>
                    <td>
                        <div class="table-person-meta">
                            <strong><?= e($record['member_name']) ?></strong>
                            <span><?= e($record['member_email']) ?></span>
                        </div>
                    </td>
                    <td class="small"><?= e($record['institution_name'] ?: 'Unknown') ?></td>
                    <td class="small">
                        <?= e($record['faculty_name'] ?: '—') ?>
                        <?php if (!empty($record['department_name'])): ?><br><span class="tiny muted"><?= e($record['department_name']) ?></span><?php endif; ?>
                        <?php if (!empty($record['programme'])): ?><br><span class="tiny muted"><?= e($record['programme']) ?></span><?php endif; ?>
                    </td>
                    <td class="small"><?= e($record['graduation_year'] ?: '—') ?></td>
                    <td class="small muted nowrap"><?= e(short_date($record['created_at'])) ?></td>
                    <td>
                        <div class="table-actions">
                            <form method="post" action="<?= url('admin/education/' . $record['id'] . '/status') ?>" data-confirm="Confirm this education record?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-primary btn-sm">Confirm</button>
                            </form>
                            <form method="post" action="<?= url('admin/education/' . $record['id'] . '/status') ?>" class="flex" style="gap:4px">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="reject">
                                <input type="text" name="rejection_reason" maxlength="255" placeholder="Reason" style="width:120px">
                                <button type="submit" class="btn btn-ghost btn-sm">Reject</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><h2>Recently reviewed</h2></div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Member</th><th>Institution</th><th>Year</th><th>Status</th><th>Reviewer</th><th>Date</th></tr></thead>
            <tbody>
            <?php if ($reviewed === []): ?>
                <tr><td colspan="6" class="empty-row">Nothing reviewed yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($reviewed as $record): ?>
                <tr>
                    <td><?= e($record['member_name']) ?></td>
                    <td class="small"><?= e($record['institution_name'] ?: 'Unknown') ?></td>
                    <td><?= e($record['graduation_year'] ?: '—') ?></td>
                    <td><span class="badge badge-<?= $record['status'] === 'verified' ? 'success' : 'danger' ?>"><?= e(ucfirst($record['status'])) ?></span></td>
                    <td class="small"><?= e($record['reviewer_name'] ?? '—') ?></td>
                    <td class="small muted nowrap"><?= e(short_date($record['verified_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
