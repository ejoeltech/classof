<?php $title = 'Sign in'; ?>
<div class="auth-head">
    <h1>Welcome back</h1>
    <p>Sign in to your alumni workspace.</p>
</div>

<form method="post" action="<?= url('login') ?>" class="auth-form" data-submit-once>
    <?= csrf_field() ?>

    <div class="field">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" required autofocus autocomplete="email" class="<?= isset($errors['email']) ? 'is-invalid' : '' ?>" placeholder="you@example.com">
        <?php if (isset($errors['email'])): ?><span class="error-text"><?= e($errors['email']) ?></span><?php endif; ?>
    </div>

    <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password" class="<?= isset($errors['password']) ? 'is-invalid' : '' ?>" placeholder="Your password">
        <?php if (isset($errors['password'])): ?><span class="error-text"><?= e($errors['password']) ?></span><?php endif; ?>
    </div>

    <div class="auth-row">
        <label class="checkbox">
            <input type="checkbox" name="remember" value="1">
            <span>Keep me signed in</span>
        </label>
        <a href="<?= url('forgot-password') ?>">Forgot password?</a>
    </div>

    <button type="submit" class="btn btn-primary btn-lg btn-block">Sign in</button>
</form>

<p class="auth-alt">New to Classof? <a href="<?= url('register') ?>">Create an account</a></p>

<?php if (config('app.env') === 'local'): ?>
    <div class="auth-divider">Demo accounts</div>
    <div class="callout info" style="font-size:12.5px">
        <?= icon('info', 16) ?>
        <div>
            <strong>demo@classof.test</strong> / password123 — group owner<br>
            <strong>admin@classof.test</strong> / password123 — platform admin
        </div>
    </div>
<?php endif; ?>
