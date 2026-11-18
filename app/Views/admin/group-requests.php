<?php $title = 'Group requests'; ?>
<div class="page-head">
    <div>
        <h1>Group requests</h1>
        <p>Verify that a requested group is a real affiliation and does not duplicate an existing group.</p>
    </div>
    <div class="page-head-actions">
        <a href="<?= url('admin/institutions') ?>" class="btn btn-outline">Institutions</a>
        <a href="<?= url('admin') ?>" class="btn btn-ghost">Back to console</a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><h2>Pending group requests</h2><span class="badge badge-warn"><?= count($pending) ?></span></div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Requested group</th><th>Institution</th><th>Affiliation</th><th>Requester</th><th>Date</th><th class="text-right">Review</th></tr></thead>
            <tbody>
            <?php if ($pending === []): ?>
                <tr><td colspan="6" class="empty-row">No pending group requests.</td></tr>
            <?php endif; ?>
            <?php foreach ($pending as $request): ?>
                <tr>
                    <td>
                        <div class="row-title"><?= e($request['name']) ?></div>
                        <div class="tiny muted"><?= e(App\Models\Group::typeLabel($request['type'])) ?></div>
                    </td>
                    <td class="small"><?= e($request['institution_name']) ?></td>
                    <td class="small">
                        <?= e(App\Models\Group::scopeLabel($request['scope'])) ?>
                        <?php if (!empty($request['faculty'])): ?><br><span class="tiny muted"><?= e($request['faculty']) ?></span><?php endif; ?>
                        <?php if (!empty($request['department'])): ?><br><span class="tiny muted"><?= e($request['department']) ?></span><?php endif; ?>
                        <?php if (!empty($request['graduation_year'])): ?><br><span class="tiny muted">Class of <?= e($request['graduation_year']) ?></span><?php endif; ?>
                    </td>
                    <td class="small"><?= e($request['requester_name']) ?><br><span class="tiny muted"><?= e($request['requester_email']) ?></span></td>
                    <td class="small muted nowrap"><?= e(short_date($request['created_at'])) ?></td>
                    <td>
                        <div class="table-actions">
                            <form method="post" action="<?= url('admin/group-requests/' . $request['id'] . '/status') ?>" data-confirm="Approve and publish this group? The requester becomes owner.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-primary btn-sm">Approve</button>
                            </form>
                            <form method="post" action="<?= url('admin/group-requests/' . $request['id'] . '/status') ?>" class="flex" style="gap:4px">
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
    <div class="card-header"><h2>Reviewed requests</h2></div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Group</th><th>Institution</th><th>Status</th><th>Requester</th><th>Reviewed</th></tr></thead>
            <tbody>
            <?php if ($reviewed === []): ?>
                <tr><td colspan="5" class="empty-row">Nothing reviewed yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($reviewed as $request): ?>
                <tr>
                    <td>
                        <?= e($request['name']) ?>
                        <?php if (!empty($request['created_group_slug'])): ?>
                            <a href="<?= url('groups/' . $request['created_group_slug']) ?>" class="tiny">Open</a>
                        <?php endif; ?>
                    </td>
                    <td class="small"><?= e($request['institution_name']) ?></td>
                    <td><span class="badge badge-<?= $request['status'] === 'approved' ? 'success' : 'danger' ?>"><?= e(ucfirst($request['status'])) ?></span></td>
                    <td class="small"><?= e($request['requester_name']) ?></td>
                    <td class="small muted nowrap"><?= e(short_date($request['reviewed_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
