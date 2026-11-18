<?php $title = 'Reset your password'; ?>
<div class="auth-head">
    <h1>Forgot your password?</h1>
    <p>Enter your email and we will send you a reset link.</p>
</div>

<form method="post" action="<?= url('forgot-password') ?>" class="auth-form" data-submit-once>
    <?= csrf_field() ?>

    <div class="field">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" required autofocus autocomplete="email" placeholder="you@example.com" class="<?= isset($errors['email']) ? 'is-invalid' : '' ?>">
        <?php if (isset($errors['email'])): ?><span class="error-text"><?= e($errors['email']) ?></span><?php endif; ?>
    </div>

    <button type="submit" class="btn btn-primary btn-lg btn-block">Send reset link</button>
</form>

<p class="auth-alt"><a href="<?= url('login') ?>">← Back to sign in</a></p>
