<?php $title = 'Private group'; ?>
<div class="card" style="max-width:560px;margin:60px auto">
    <div class="card-body" style="padding:40px 32px;text-align:center">
        <span class="empty-icon" style="margin:0 auto 16px"><?= icon('lock', 22) ?></span>
        <h1 style="font-size:22px;margin-bottom:8px">This group is private</h1>
        <p class="muted"><?= e($group['name']) ?> is not open to the public. Ask a group admin to send you an invitation link, then open it while signed in as the invited email address.</p>
        <div class="flex" style="justify-content:center;margin-top:22px">
            <a href="<?= url('dashboard') ?>" class="btn btn-outline">Back to dashboard</a>
            <a href="<?= url('explore') ?>" class="btn btn-primary">Explore public groups</a>
        </div>
    </div>
</div>
