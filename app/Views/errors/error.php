<?php
$code = $code ?? 404;
$message = $message ?? 'Something went wrong.';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= (int) $code ?> · <?= e(config('app.name')) ?></title>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="auth-body">
<div style="min-height:100vh;display:grid;place-items:center;padding:24px">
    <div style="max-width:480px;text-align:center">
        <a href="<?= url('/') ?>" class="brand" style="justify-content:center;margin-bottom:22px">
            <span class="brand-mark">C</span>
            <span class="brand-word">classof<span class="brand-dot">.</span></span>
        </a>
        <p style="font-family:var(--serif);font-size:64px;line-height:1;color:var(--forest);margin:0 0 8px"><?= (int) $code ?></p>
        <h1 style="font-size:24px;margin-bottom:10px"><?= (int) $code === 404 ? 'Page not found' : 'Access denied' ?></h1>
        <p class="muted" style="margin-bottom:24px"><?= e($message) ?></p>
        <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
            <a href="javascript:history.back()" class="btn btn-outline">Go back</a>
            <a href="<?= url('dashboard') ?>" class="btn btn-primary">Open dashboard</a>
        </div>
    </div>
</div>
</body>
</html>
