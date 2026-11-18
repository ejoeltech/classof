<?php
/** @var string $content */
$pageTitle = $title ?? 'Classof';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> · <?= e(config('app.name')) ?></title>
    <meta name="description" content="Classof is the alumni group manager for primary schools, secondary schools and universities — with welfare management and secure exco voting.">
    <link rel="icon" href="<?= asset('favicon.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="public-body">
<header class="public-nav">
    <div class="container public-nav-inner">
        <a href="<?= url('/') ?>" class="brand">
            <span class="brand-mark">C</span>
            <span class="brand-word">classof<span class="brand-dot">.</span></span>
        </a>
        <nav class="public-links">
            <a href="<?= url('explore') ?>">Explore groups</a>
            <a href="<?= url('/') ?>#features">Features</a>
            <a href="<?= url('/') ?>#how">How it works</a>
        </nav>
        <div class="public-actions">
            <a href="<?= url('login') ?>" class="btn btn-ghost btn-sm">Sign in</a>
            <a href="<?= url('register') ?>" class="btn btn-primary btn-sm">Get started</a>
        </div>
    </div>
</header>

<main>
    <?= $content ?>
</main>

<footer class="public-footer">
    <div class="container public-footer-inner">
        <div>
            <a href="<?= url('/') ?>" class="brand brand-light">
                <span class="brand-mark">C</span>
                <span class="brand-word">classof<span class="brand-dot">.</span></span>
            </a>
            <p>The alumni group manager with welfare and elections built in.</p>
        </div>
        <div class="public-footer-links">
            <a href="<?= url('explore') ?>">Explore groups</a>
            <a href="<?= url('register') ?>">Create an account</a>
            <a href="<?= url('login') ?>">Sign in</a>
        </div>
        <p class="public-copyright">&copy; <?= date('Y') ?> <?= e(config('app.name')) ?>. All rights reserved.</p>
    </div>
</footer>
</body>
</html>
