<?php $title = 'Choose a new password'; ?>
<div class="auth-head">
    <h1>Choose a new password</h1>
    <p>Resetting the password for <strong><?= e($email ?? '') ?></strong>.</p>
</div>

<form method="post" action="<?= url('reset-password') ?>" class="auth-form" data-submit-once>
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token ?? '') ?>">

    <div class="field">
        <label for="password">New password</label>
        <input type="password" id="password" name="password" required autofocus autocomplete="new-password" placeholder="At least 8 characters" class="<?= isset($errors['password']) ? 'is-invalid' : '' ?>">
        <?php if (isset($errors['password'])): ?><span class="error-text"><?= e($errors['password']) ?></span><?php endif; ?>
    </div>

    <div class="field">
        <label for="password_confirmation">Confirm new password</label>
        <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" placeholder="Repeat password">
    </div>

    <button type="submit" class="btn btn-primary btn-lg btn-block">Reset password</button>
</form>

<p class="auth-alt"><a href="<?= url('login') ?>">← Back to sign in</a></p>
