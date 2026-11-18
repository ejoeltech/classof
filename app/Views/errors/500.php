<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Something went wrong · <?= e(config('app.name')) ?></title>
    <link rel="stylesheet" href="<?= url('public/assets/css/app.css') ?>">
</head>
<body class="auth-body">
<div style="min-height:100vh;display:grid;place-items:center;padding:24px">
    <div style="max-width:480px;text-align:center">
        <a href="<?= url('/') ?>" class="brand" style="justify-content:center;margin-bottom:22px">
            <span class="brand-mark">C</span>
            <span class="brand-word">classof<span class="brand-dot">.</span></span>
        </a>
        <h1 style="font-size:24px;margin-bottom:10px">Something went wrong</h1>
        <p class="muted" style="margin-bottom:24px">We could not complete that request. The issue has been logged and we are looking into it.</p>
        <a href="<?= url('dashboard') ?>" class="btn btn-primary">Back to dashboard</a>
    </div>
</div>
</body>
</html>
