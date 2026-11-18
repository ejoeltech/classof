<?php $title = 'Institution review'; ?>
<div class="page-head">
    <div>
        <h1>Institution review</h1>
        <p>Verify institutions before they can anchor groups, education records or suggestions.</p>
    </div>
    <div class="page-head-actions">
        <a href="<?= url('admin/group-requests') ?>" class="btn btn-outline">Group requests</a>
        <a href="<?= url('admin') ?>" class="btn btn-ghost">Back to console</a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><h2>Pending institutions</h2><span class="badge badge-warn"><?= count($pending) ?></span></div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Institution</th><th>Type</th><th>Ownership</th><th>State</th><th>Submitted by</th><th>Date</th><th class="text-right">Review</th></tr></thead>
            <tbody>
            <?php if ($pending === []): ?>
                <tr><td colspan="7" class="empty-row">No pending institution requests.</td></tr>
            <?php endif; ?>
            <?php foreach ($pending as $institution): ?>
                <tr>
                    <td class="row-title"><?= e($institution['name']) ?></td>
                    <td class="small"><?= e(App\Models\Institution::TYPES[$institution['type']] ?? $institution['type']) ?></td>
                    <td class="small"><?= e(ucfirst($institution['ownership'])) ?></td>
                    <td class="small"><?= e($institution['state'] ?: '—') ?></td>
                    <td class="small"><?= e($institution['submitter_name'] ?? 'Unknown') ?><br><span class="tiny muted"><?= e($institution['submitter_email'] ?? '') ?></span></td>
                    <td class="small muted nowrap"><?= e(short_date($institution['created_at'])) ?></td>
                    <td>
                        <div class="table-actions">
                            <form method="post" action="<?= url('admin/institutions/' . $institution['id'] . '/status') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-primary btn-sm">Verify</button>
                            </form>
                            <form method="post" action="<?= url('admin/institutions/' . $institution['id'] . '/status') ?>" class="flex" style="gap:4px">
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
            <thead><tr><th>Institution</th><th>Status</th><th>Reviewed by</th><th>Date</th></tr></thead>
            <tbody>
            <?php if ($reviewed === []): ?>
                <tr><td colspan="4" class="empty-row">Nothing reviewed yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($reviewed as $institution): ?>
                <tr>
                    <td><?= e($institution['name']) ?></td>
                    <td><span class="badge badge-<?= $institution['status'] === 'verified' ? 'success' : 'danger' ?>"><?= e(ucfirst($institution['status'])) ?></span></td>
                    <td class="small"><?= e($institution['reviewer_name'] ?? '—') ?></td>
                    <td class="small muted nowrap"><?= e(short_date($institution['reviewed_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
