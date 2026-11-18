<?php
$title = $event['title'];
require BASE_PATH . '/app/Views/partials/group-header.php';
$ts = strtotime((string) $event['starts_at']);
$ends = !empty($event['ends_at']) ? strtotime((string) $event['ends_at']) : null;
$isPast = $ts < time();
$rsvpStatus = $myRsvp['status'] ?? '';
?>
<div class="page-head">
    <div>
        <a href="<?= url('groups/' . $group['slug'] . '/events') ?>" class="tiny muted" style="display:inline-flex;align-items:center;gap:4px;margin-bottom:6px"><?= icon('chevron-right', 13) ?> All events</a>
        <h1><?= e($event['title']) ?></h1>
        <p><?= e(date('l, F j, Y · g:i A', $ts)) ?><?= $ends !== null ? ' – ' . e(date('g:i A', $ends)) : '' ?></p>
    </div>
    <div class="page-head-actions">
        <a href="<?= url('groups/' . $group['slug'] . '/events/' . $event['id'] . '/calendar') ?>" class="btn btn-outline">
            <?= icon('calendar', 16) ?> Add to calendar
        </a>
        <a href="<?= e(whatsapp_share_url(null, $event['title'] . ' — ' . date('D, M j, Y · g:i A', $ts) . ' — ' . absolute_url('groups/' . $group['slug'] . '/events/' . $event['id']))) ?>" target="_blank" rel="noopener" class="btn btn-ghost">
            <?= icon('message', 15) ?> Share on WhatsApp
        </a>
    </div>
</div>

<div class="split-main">
    <div class="stack">
        <section class="card">
            <div class="card-body">
                <div class="flex" style="align-items:flex-start;gap:18px">
                    <div class="date-chip" style="width:64px">
                        <span><?= date('M', $ts) ?></span>
                        <strong style="font-size:24px"><?= date('j', $ts) ?></strong>
                    </div>
                    <div style="flex:1">
                        <div class="meta-row">
                            <span><?= icon('clock', 15) ?> <?= e(date('D, M j · g:i A', $ts)) ?><?= $ends !== null ? ' – ' . e(date('g:i A', $ends)) : '' ?></span>
                            <?php if (!empty($event['location'])): ?>
                                <span><?= icon('location', 15) ?> <?= e($event['location']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($event['capacity'])): ?>
                                <span><?= icon('users', 15) ?> <?= (int) $event['capacity'] ?> capacity</span>
                            <?php endif; ?>
                            <?php if ((float) $event['fee'] > 0): ?>
                                <span class="badge badge-gold"><?= money($event['fee'], $group['currency']) ?> entry</span>
                            <?php else: ?>
                                <span class="badge badge-success">Free entry</span>
                            <?php endif; ?>
                            <?php if ($isPast): ?>
                                <span class="badge badge-neutral">Past event</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="prose mt-3" style="border-top:1px solid var(--line);padding-top:18px">
                    <?php if (!empty($event['description'])): ?>
                        <?= nl2br(e($event['description'])) ?>
                    <?php else: ?>
                        <p class="muted">No description was provided for this event.</p>
                    <?php endif; ?>
                </div>

                <div class="pill-row mt-3">
                    <span class="badge badge-success"><?= (int) $counts['going'] ?> going</span>
                    <span class="badge badge-warn"><?= (int) $counts['maybe'] ?> maybe</span>
                    <span class="badge badge-neutral"><?= (int) $counts['declined'] ?> declined</span>
                </div>
            </div>
        </section>

        <?php if ($isManager): ?>
            <section class="card">
                <div class="card-header">
                    <h2>Attendees</h2>
                    <span class="badge badge-neutral"><?= count($attendees) ?> responses</span>
                </div>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Member</th><th>RSVP</th><th>Guests</th></tr></thead>
                        <tbody>
                        <?php if ($attendees === []): ?>
                            <tr><td colspan="3" class="empty-row">No RSVPs yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($attendees as $attendee): ?>
                            <tr>
                                <td>
                                    <div class="table-person">
                                        <?= avatar($attendee['name'], $attendee['avatar'] ?? null, 32) ?>
                                        <div class="table-person-meta">
                                            <strong><?= e($attendee['name']) ?></strong>
                                            <span><?= e($attendee['email']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $attendee['status'] === 'going' ? 'success' : ($attendee['status'] === 'maybe' ? 'warn' : 'neutral') ?>">
                                        <?= e(status_label($attendee['status'])) ?>
                                    </span>
                                </td>
                                <td><?= (int) $attendee['guests'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <div class="stack">
        <section class="card">
            <div class="card-header">
                <h2>Your RSVP</h2>
                <?php if ($rsvpStatus !== ''): ?>
                    <span class="badge badge-<?= $rsvpStatus === 'going' ? 'success' : ($rsvpStatus === 'maybe' ? 'warn' : 'neutral') ?>"><?= e(status_label($rsvpStatus)) ?></span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form method="post" action="<?= url('groups/' . $group['slug'] . '/events/' . $event['id'] . '/rsvp') ?>" data-submit-once>
                    <?= csrf_field() ?>
                    <div class="stack-sm">
                        <div class="field">
                            <label for="status">Will you attend?</label>
                            <select id="status" name="status" class="select">
                                <option value="going" <?= $rsvpStatus === 'going' ? 'selected' : '' ?>>Yes, I will be there</option>
                                <option value="maybe" <?= $rsvpStatus === 'maybe' ? 'selected' : '' ?>>Maybe</option>
                                <option value="declined" <?= $rsvpStatus === 'declined' ? 'selected' : '' ?>>No, I cannot make it</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="guests">Guests accompanying you</label>
                            <input type="number" id="guests" name="guests" min="0" max="10" value="<?= (int) ($myRsvp['guests'] ?? 0) ?>">
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Save RSVP</button>
                    </div>
                </form>
            </div>
        </section>

        <?php if ((float) $event['fee'] > 0): ?>
            <section class="card">
                <div class="card-header"><h2>Event fee</h2></div>
                <div class="card-body">
                    <?php if ($payment !== null): ?>
                        <div class="status-line success mb-2">
                            <?= icon('check', 16) ?> Paid — reference <?= e($payment['reference']) ?>
                        </div>
                        <a href="<?= url('payments/' . $payment['id'] . '/instructions') ?>" class="btn btn-outline btn-block">View receipt</a>
                    <?php else: ?>
                        <p class="muted small">Entry for this event is <strong><?= money($event['fee'], $group['currency']) ?></strong>. Paying also marks you as going.</p>
                        <form method="post" action="<?= url('payments/initiate') ?>" data-submit-once>
                            <?= csrf_field() ?>
                            <input type="hidden" name="purpose" value="event">
                            <input type="hidden" name="group_id" value="<?= (int) $group['id'] ?>">
                            <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                            <button type="submit" class="btn btn-primary btn-block">Pay <?= money($event['fee'], $group['currency']) ?></button>
                        </form>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($isManager): ?>
            <section class="card danger-zone">
                <h3>Manage event</h3>
                <p class="small muted mt-1">Deleting removes the event and all RSVPs.</p>
                <form method="post" action="<?= url('groups/' . $group['slug'] . '/events/' . $event['id'] . '/delete') ?>" data-confirm="Delete this event?">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger-ghost btn-sm"><?= icon('trash', 15) ?> Delete event</button>
                </form>
            </section>
        <?php endif; ?>
    </div>
</div>
