<?php
/** @var string $content */
$pageTitle = $title ?? 'Welcome';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> · <?= e(config('app.name')) ?></title>
    <link rel="icon" href="<?= asset('favicon.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="auth-body">
<div class="auth-shell">
    <section class="auth-panel">
        <a href="<?= url('/') ?>" class="brand brand-light">
            <span class="brand-mark">C</span>
            <span class="brand-word">classof<span class="brand-dot">.</span></span>
        </a>

        <div class="auth-panel-content">
            <p class="eyebrow">Alumni, organised.</p>
            <h2>Where your class stays connected.</h2>
            <p class="auth-panel-lead">One home for your alumni community — member directory, dues, welfare support and transparent executive elections.</p>

            <ul class="auth-benefits">
                <li><?= icon('users', 18) ?> <span>A living directory of every classmate</span></li>
                <li><?= icon('heart', 18) ?> <span>Welfare cases with a visible contribution ledger</span></li>
                <li><?= icon('vote', 18) ?> <span>Secret, one-member-one-vote exco elections</span></li>
                <li><?= icon('chart', 18) ?> <span>A treasury every member can trust</span></li>
            </ul>
        </div>

        <p class="auth-panel-foot">Built for primary, secondary and university alumni groups.</p>
    </section>

    <section class="auth-form-side">
        <div class="auth-form-wrap">
            <?php require BASE_PATH . '/app/Views/partials/flash.php'; ?>
            <?= $content ?>
        </div>
    </section>
</div>
</body>
</html>
