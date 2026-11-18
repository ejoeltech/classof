<?php $title = 'Create your account'; ?>
<div class="auth-head">
    <h1>Create your account</h1>
    <p>Join your classmates and keep your alumni group organised.</p>
</div>

<form method="post" action="<?= url('register') ?>" class="auth-form" data-submit-once>
    <?= csrf_field() ?>

    <div class="field">
        <label for="name">Full name</label>
        <input type="text" id="name" name="name" value="<?= e(old('name')) ?>" required autofocus autocomplete="name" placeholder="e.g. Tunde Bakare" class="<?= isset($errors['name']) ? 'is-invalid' : '' ?>">
        <?php if (isset($errors['name'])): ?><span class="error-text"><?= e($errors['name']) ?></span><?php endif; ?>
    </div>

    <div class="field">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" required autocomplete="email" placeholder="you@example.com" class="<?= isset($errors['email']) ? 'is-invalid' : '' ?>">
        <?php if (isset($errors['email'])): ?><span class="error-text"><?= e($errors['email']) ?></span><?php endif; ?>
    </div>

    <div class="field">
        <label for="phone">Phone <span class="hint">(optional)</span></label>
        <input type="tel" id="phone" name="phone" value="<?= e(old('phone')) ?>" autocomplete="tel" placeholder="+234 800 000 0000" class="<?= isset($errors['phone']) ? 'is-invalid' : '' ?>">
        <?php if (isset($errors['phone'])): ?><span class="error-text"><?= e($errors['phone']) ?></span><?php endif; ?>
    </div>

    <div class="form-grid">
        <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required autocomplete="new-password" placeholder="At least 8 characters" class="<?= isset($errors['password']) ? 'is-invalid' : '' ?>">
            <?php if (isset($errors['password'])): ?><span class="error-text"><?= e($errors['password']) ?></span><?php endif; ?>
        </div>
        <div class="field">
            <label for="password_confirmation">Confirm password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" placeholder="Repeat password">
        </div>
    </div>

    <button type="submit" class="btn btn-primary btn-lg btn-block">Create account</button>
</form>

<p class="auth-alt">Already have an account? <a href="<?= url('login') ?>">Sign in</a></p>
