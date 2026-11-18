<?php if (!empty($flashStatus)): ?>
    <div class="alert alert-success" role="status" data-flash>
        <span class="alert-icon"><?= icon('check', 18) ?></span>
        <div class="alert-body"><?= e($flashStatus) ?></div>
        <button type="button" class="alert-close" data-dismiss aria-label="Dismiss"><?= icon('x', 15) ?></button>
    </div>
<?php endif; ?>

<?php if (!empty($flashError)): ?>
    <div class="alert alert-error" role="alert" data-flash>
        <span class="alert-icon"><?= icon('alert', 18) ?></span>
        <div class="alert-body"><?= e($flashError) ?></div>
        <button type="button" class="alert-close" data-dismiss aria-label="Dismiss"><?= icon('x', 15) ?></button>
    </div>
<?php endif; ?>

<?php $devLink = \App\Core\Session::getFlash('dev_link'); ?>
<?php if (!empty($devLink)): ?>
    <div class="alert alert-info">
        <span class="alert-icon"><?= icon('info', 18) ?></span>
        <div class="alert-body">
            <strong>Local mode:</strong> use this one-time link to reset the password.
            <div class="copy-row">
                <input type="text" value="<?= e($devLink) ?>" readonly data-select>
                <button type="button" class="btn btn-outline btn-sm" data-copy="<?= e($devLink) ?>">Copy</button>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php $inviteLink = \App\Core\Session::getFlash('invite_link'); ?>
<?php if (!empty($inviteLink)): ?>
    <div class="alert alert-info">
        <span class="alert-icon"><?= icon('link', 18) ?></span>
        <div class="alert-body">
            <strong>Invitation link ready.</strong> Email delivery is logged locally, so share this link directly.
            <div class="copy-row">
                <input type="text" value="<?= e($inviteLink) ?>" readonly data-select>
                <button type="button" class="btn btn-outline btn-sm" data-copy="<?= e($inviteLink) ?>">Copy</button>
            </div>
        </div>
    </div>
<?php endif; ?>
