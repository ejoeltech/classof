<?php $title = 'Platform settings'; ?>
<div class="page-head">
    <div>
        <h1>Platform settings</h1>
        <p>Control education verification, classmate discovery, chatbots and WhatsApp delivery.</p>
    </div>
    <div class="page-head-actions">
        <a href="<?= url('admin/education') ?>" class="btn btn-outline">Education queue</a>
        <a href="<?= url('admin') ?>" class="btn btn-ghost">Back to console</a>
    </div>
</div>

<form method="post" action="<?= url('admin/settings') ?>" class="card" data-submit-once>
    <?= csrf_field() ?>
    <div class="card-body">
        <div class="stack">
            <?php foreach ($definitions as $key => $definition): ?>
                <?php $name = str_replace('.', '_', $key); $value = $settings[$key]['value'] ?? $definition['value']; ?>
                <div class="between" style="align-items:flex-start;gap:18px;padding-bottom:16px;border-bottom:1px solid var(--line)">
                    <div style="max-width:640px;flex:1">
                        <?php if ($definition['type'] === 'boolean'): ?>
                            <label class="checkbox" style="font-weight:600;color:var(--ink)">
                                <input type="checkbox" name="<?= e($name) ?>" value="1" <?= in_array($value, ['1', 'true'], true) ? 'checked' : '' ?>>
                                <span><?= e($definition['label']) ?></span>
                            </label>
                        <?php else: ?>
                            <label for="<?= e($name) ?>" style="font-weight:600;color:var(--ink);display:block;margin-bottom:6px"><?= e($definition['label']) ?></label>
                            <input type="number" id="<?= e($name) ?>" name="<?= e($name) ?>" min="0" value="<?= e($value) ?>" style="max-width:160px">
                        <?php endif; ?>
                        <p class="muted small" style="margin:6px 0 0 <?= $definition['type'] === 'boolean' ? '27px' : '0' ?>"><?= e($definition['help']) ?></p>
                    </div>
                    <span class="badge badge-<?= $definition['type'] === 'boolean' ? (in_array($value, ['1', 'true'], true) ? 'success' : 'neutral') : 'info' ?>">
                        <?= $definition['type'] === 'boolean' ? (in_array($value, ['1', 'true'], true) ? 'On' : 'Off') : e($value) ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="callout mt-3">
            <?= icon('alert', 17) ?>
            <div><strong>Auto-approval removes proof review for institution-wide groups.</strong> Only enable it if you accept self-declared education claims as sufficient for that scope. Class, faculty, department and association groups still require approval; invite-only groups always require an invite.</div>
        </div>
    </div>
    <div class="card-footer between">
        <span class="muted small">Changes are recorded in the audit trail.</span>
        <button type="submit" class="btn btn-primary">Save settings</button>
    </div>
</form>
