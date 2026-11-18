<?php
$title = 'WhatsApp outbox';
$statusTone = static fn (string $s): string => match ($s) {
    'sent' => 'success',
    'pending' => 'warn',
    'failed' => 'danger',
    'processing' => 'info',
    default => 'neutral',
};
?>
<div class="page-head">
    <div>
        <h1>WhatsApp outbox</h1>
        <p>Queued deliveries, driver status and retry controls.</p>
    </div>
    <div class="page-head-actions">
        <a href="<?= url('admin/settings') ?>" class="btn btn-outline">Notification settings</a>
        <a href="<?= url('admin') ?>" class="btn btn-ghost">Back to console</a>
    </div>
</div>

<div class="stat-grid mb-3">
    <div class="stat-card"><span class="stat-icon warn"><?= icon('clock', 20) ?></span><div class="stat-meta"><span class="stat-label">Pending</span><span class="stat-value"><?= (int) $counts['pending'] ?></span></div></div>
    <div class="stat-card"><span class="stat-icon"><?= icon('check', 20) ?></span><div class="stat-meta"><span class="stat-label">Sent</span><span class="stat-value"><?= (int) $counts['sent'] ?></span></div></div>
    <div class="stat-card"><span class="stat-icon warn"><?= icon('alert', 20) ?></span><div class="stat-meta"><span class="stat-label">Failed</span><span class="stat-value"><?= (int) $counts['failed'] ?></span></div></div>
    <div class="stat-card"><span class="stat-icon info"><?= icon('x', 20) ?></span><div class="stat-meta"><span class="stat-label">Cancelled</span><span class="stat-value"><?= (int) $counts['cancelled'] ?></span></div></div>
</div>

<div class="callout mb-3">
    <?= icon('info', 17) ?>
    <div>
        Driver: <strong><?= e(ucfirst($driver)) ?></strong>.
        <?php if ($driver === 'log' || !$configured): ?>
            Messages are written to <code>storage/logs/whatsapp.log</code> because Cloud API credentials are missing.
        <?php else: ?>
            Cloud API credentials are present.
        <?php endif; ?>
        Delivery is <strong><?= $enabled ? 'enabled' : 'disabled' ?></strong>.
        Run <code>php database/whatsapp_worker.php</code> from cron to process the queue.
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Created</th><th>Member</th><th>Group</th><th>Template</th><th>Status</th><th>Provider</th><th>Attempts</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            <?php if ($recent === []): ?>
                <tr><td colspan="8" class="empty-row">No WhatsApp deliveries queued yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($recent as $delivery): ?>
                <tr>
                    <td class="small muted nowrap"><?= e(short_date($delivery['created_at'])) ?></td>
                    <td><?= e($delivery['user_name']) ?></td>
                    <td class="small"><?= e($delivery['group_name'] ?: '—') ?></td>
                    <td class="tiny" style="font-family:ui-monospace,monospace"><?= e($delivery['template']) ?></td>
                    <td><span class="badge badge-<?= $statusTone($delivery['status']) ?>"><?= e(ucfirst($delivery['status'])) ?></span></td>
                    <td class="small muted"><?= e($delivery['provider_status'] ?: '—') ?></td>
                    <td><?= (int) $delivery['attempts'] ?></td>
                    <td>
                        <div class="table-actions">
                            <?php if (in_array($delivery['status'], ['failed', 'cancelled'], true)): ?>
                                <form method="post" action="<?= url('admin/whatsapp/' . $delivery['id'] . '/retry') ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-outline btn-sm">Retry</button>
                                </form>
                            <?php endif; ?>
                            <?php if (in_array($delivery['status'], ['pending', 'failed'], true)): ?>
                                <form method="post" action="<?= url('admin/whatsapp/' . $delivery['id'] . '/cancel') ?>" data-confirm="Cancel this delivery?">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-ghost btn-sm">Cancel</button>
                                </form>
                            <?php endif; ?>
                            <?php if (!empty($delivery['last_error'])): ?>
                                <span class="tiny" style="color:var(--danger);max-width:180px;display:inline-block"><?= e(str_limit($delivery['last_error'], 70)) ?></span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
