<?php
$title = 'Events';
require BASE_PATH . '/app/Views/partials/group-header.php';
?>
<div class="page-head">
    <div>
        <h1>Events</h1>
        <p>Reunions, homecomings, webinars and everything in between.</p>
    </div>
    <?php if ($isManager): ?>
        <button type="button" class="btn btn-primary" data-modal-open="event-modal"><?= icon('plus', 16) ?> Create event</button>
    <?php endif; ?>
</div>

<div class="filter-bar">
    <a href="<?= url('groups/' . $group['slug'] . '/events?scope=upcoming') ?>" class="btn btn-sm <?= $scope === 'upcoming' ? 'btn-primary' : 'btn-outline' ?>">Upcoming</a>
    <a href="<?= url('groups/' . $group['slug'] . '/events?scope=past') ?>" class="btn btn-sm <?= $scope === 'past' ? 'btn-primary' : 'btn-outline' ?>">Past events</a>
</div>

<?php if ($events === []): ?>
    <div class="card">
        <div class="empty-state">
            <span class="empty-icon"><?= icon('calendar', 22) ?></span>
            <h3><?= $scope === 'past' ? 'No past events' : 'No upcoming events' ?></h3>
            <p><?= $isManager ? 'Create an event and let your classmates RSVP.' : 'The exco will publish events here.' ?></p>
            <?php if ($isManager && $scope === 'upcoming'): ?>
                <button type="button" class="btn btn-primary btn-sm mt-2" data-modal-open="event-modal">Create the first event</button>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="stack">
        <?php foreach ($events as $event): ?>
            <?php $ts = strtotime((string) $event['starts_at']); ?>
            <article class="card">
                <div class="card-body">
                    <div class="flex" style="align-items:flex-start;gap:16px">
                        <div class="date-chip" style="width:60px">
                            <span><?= date('M', $ts) ?></span>
                            <strong style="font-size:22px"><?= date('j', $ts) ?></strong>
                        </div>
                        <div style="flex:1;min-width:0">
                            <div class="between" style="align-items:flex-start">
                                <div>
                                    <h2 style="font-size:17px;margin-bottom:4px">
                                        <a href="<?= url('groups/' . $group['slug'] . '/events/' . $event['id']) ?>" style="color:inherit"><?= e($event['title']) ?></a>
                                    </h2>
                                    <div class="meta-row">
                                        <span><?= icon('clock', 15) ?> <?= e(date('D, M j · g:i A', $ts)) ?></span>
                                        <?php if (!empty($event['location'])): ?>
                                            <span><?= icon('location', 15) ?> <?= e($event['location']) ?></span>
                                        <?php endif; ?>
                                        <?php if ((float) $event['fee'] > 0): ?>
                                            <span class="badge badge-gold"><?= money($event['fee'], $group['currency']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex" style="gap:6px">
                                    <?php if ((int) $event['going_count'] > 0): ?>
                                        <span class="badge badge-success"><?= (int) $event['going_count'] ?> going</span>
                                    <?php endif; ?>
                                    <?php if ((int) $event['maybe_count'] > 0): ?>
                                        <span class="badge badge-warn"><?= (int) $event['maybe_count'] ?> maybe</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if (!empty($event['description'])): ?>
                                <p class="list-item-text mt-2"><?= e(str_limit($event['description'], 220)) ?></p>
                            <?php endif; ?>

                            <div class="flex mt-2">
                                <a href="<?= url('groups/' . $group['slug'] . '/events/' . $event['id']) ?>" class="btn btn-outline btn-sm">View event</a>
                                <?php if ($isManager): ?>
                                    <form method="post" action="<?= url('groups/' . $group['slug'] . '/events/' . $event['id'] . '/delete') ?>" data-confirm="Delete this event and all RSVPs?">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-danger-ghost btn-sm">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($isManager): ?>
    <div class="modal" id="event-modal" role="dialog" aria-modal="true" aria-labelledby="event-modal-title">
        <div class="modal-backdrop" data-modal-close></div>
        <div class="modal-panel wide">
            <form method="post" action="<?= url('groups/' . $group['slug'] . '/events') ?>" data-submit-once>
                <?= csrf_field() ?>
                <div class="modal-head">
                    <h2 id="event-modal-title">Create an event</h2>
                    <button type="button" class="icon-btn" data-modal-close aria-label="Close"><?= icon('x', 18) ?></button>
                </div>
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="field span-2">
                            <label for="event-title">Event title</label>
                            <input type="text" id="event-title" name="title" required placeholder="e.g. Class of 2010 Annual Reunion" value="<?= e(old('title')) ?>">
                        </div>
                        <div class="field">
                            <label for="starts_at">Starts</label>
                            <input type="datetime-local" id="starts_at" name="starts_at" required value="<?= e(old('starts_at')) ?>">
                        </div>
                        <div class="field">
                            <label for="ends_at">Ends <span class="hint">(optional)</span></label>
                            <input type="datetime-local" id="ends_at" name="ends_at" value="<?= e(old('ends_at')) ?>">
                        </div>
                        <div class="field">
                            <label for="location">Location</label>
                            <input type="text" id="location" name="location" placeholder="e.g. School assembly hall, or Zoom" value="<?= e(old('location')) ?>">
                        </div>
                        <div class="field">
                            <label for="capacity">Capacity <span class="hint">(optional)</span></label>
                            <input type="number" id="capacity" name="capacity" min="1" placeholder="e.g. 150" value="<?= e(old('capacity')) ?>">
                        </div>
                        <div class="field">
                            <label for="fee">Fee per attendee</label>
                            <input type="number" id="fee" name="fee" min="0" step="0.01" placeholder="0" value="<?= e(old('fee', '0')) ?>">
                            <span class="hint">Leave as 0 for a free event.</span>
                        </div>
                        <div class="field span-2">
                            <label for="event-description">Description</label>
                            <textarea id="event-description" name="description" class="textarea" placeholder="What should attendees expect?"><?= e(old('description')) ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-primary">Publish event</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
