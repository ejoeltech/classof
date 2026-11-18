<?php
$logo = $group['logo'] ?? null;
$headline = trim((string) ($site['headline'] ?? '')) ?: (string) $group['name'];
$tagline = trim((string) ($site['tagline'] ?? ''));
$descriptor = App\Models\Group::descriptor($group);
$about = trim((string) ($site['about'] ?? ''));

if ($about === '') {
    $about = trim((string) ($group['description'] ?? ''));
}

if ($about === '') {
    $about = 'This is the alumni home for ' . $descriptor . '. Members share news, organise events, support one another and stay connected through Classof.';
}

$joinUrl = main_url('join/' . $group['slug']);
$loginUrl = main_url('login');
$canRequest = ($group['visibility'] ?? 'public') === 'public' && ($group['join_policy'] ?? 'approval') !== 'invite';
$ctaUrl = $canRequest ? $joinUrl : '#contact';
$ctaLabel = $canRequest ? 'Request to join' : 'Ask about membership';
$showJoin = !empty($site['show_join']);

$safeUrl = static function (?string $url): ?string {
    $url = trim((string) $url);

    return preg_match('#^https?://#i', $url) === 1 ? $url : null;
};

$socials = array_filter([
    'Facebook' => $safeUrl($site['facebook_url'] ?? null),
    'Instagram' => $safeUrl($site['instagram_url'] ?? null),
    'X' => $safeUrl($site['x_url'] ?? null),
    'LinkedIn' => $safeUrl($site['linkedin_url'] ?? null),
    'YouTube' => $safeUrl($site['youtube_url'] ?? null),
    'WhatsApp' => $safeUrl($site['whatsapp_url'] ?? null),
]);

$phoneHref = !empty($site['contact_phone']) ? 'tel:' . preg_replace('/[^0-9+]/', '', (string) $site['contact_phone']) : null;
?>
<?php if ($preview): ?>
    <div class="site-preview-bar">
        <span><strong>Preview</strong> · <?= ($site['status'] ?? 'draft') === 'published' ? 'published page' : 'draft page' ?></span>
        <div>
            <a href="<?= url('groups/' . $group['slug'] . '/site') ?>">Back to editor</a>
            <a href="<?= e(group_site_url((string) $site['subdomain'])) ?>" target="_blank" rel="noopener">Open live site</a>
        </div>
    </div>
<?php endif; ?>

<header class="site-header">
    <div class="site-container site-header-inner">
        <a href="<?= e(group_site_url((string) $site['subdomain'])) ?>" class="site-brand">
            <span class="site-brand-mark">
                <?php if (!empty($logo)): ?>
                    <img src="<?= e(upload_url($logo)) ?>" alt="">
                <?php else: ?>
                    <?= e(mb_substr((string) $group['name'], 0, 1)) ?>
                <?php endif; ?>
            </span>
            <span class="site-brand-text">
                <strong><?= e($group['name']) ?></strong>
                <small><?= e($descriptor) ?></small>
            </span>
        </a>

        <nav class="site-nav" aria-label="Page sections">
            <a href="#about">About</a>
            <?php if ($announcements !== []): ?><a href="#news">News</a><?php endif; ?>
            <?php if ($events !== []): ?><a href="#events">Events</a><?php endif; ?>
            <?php if ($exco !== []): ?><a href="#leadership">Leadership</a><?php endif; ?>
            <a href="#contact">Contact</a>
        </nav>

        <div class="site-header-actions">
            <a href="<?= e(whatsapp_share_url(null, $group['name'] . ' — ' . group_site_url((string) $site['subdomain']))) ?>" class="site-button site-button-ghost" target="_blank" rel="noopener">Share</a>
            <a href="<?= e($loginUrl) ?>" class="site-button site-button-ghost">Member sign in</a>
            <?php if ($showJoin): ?>
                <a href="<?= e($ctaUrl) ?>" class="site-button"><?= e($ctaLabel) ?></a>
            <?php endif; ?>
        </div>
    </div>
</header>

<section class="site-hero">
    <?php if (!empty($site['hero_image'])): ?>
        <div class="site-hero-image" style="background-image:url('<?= e(upload_url($site['hero_image'])) ?>')"></div>
    <?php endif; ?>
    <div class="site-hero-shade"></div>
    <div class="site-container site-hero-inner">
        <div class="site-kicker">
            <span><?= e(App\Models\Group::scopeLabel($group['scope'] ?? 'class')) ?></span>
            <span><?= e(App\Models\Group::typeLabel($group['type'])) ?></span>
            <?php if (!empty($group['country'])): ?><span><?= e($group['country']) ?></span><?php endif; ?>
        </div>
        <h1><?= e($headline) ?></h1>
        <?php if ($tagline !== ''): ?><p class="site-tagline"><?= e($tagline) ?></p><?php endif; ?>

        <?php if (!empty($site['show_stats'])): ?>
            <div class="site-hero-stats">
                <div><strong><?= (int) $memberCount ?></strong><span>Members</span></div>
                <?php if (!empty($site['show_announcements'])): ?>
                    <div><strong><?= (int) ($stats['announcements'] ?? 0) ?></strong><span>Updates</span></div>
                <?php endif; ?>
                <?php if (!empty($site['show_events'])): ?>
                    <div><strong><?= (int) ($stats['events'] ?? 0) ?></strong><span>Upcoming events</span></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($showJoin): ?>
            <div class="site-hero-actions">
                <a href="<?= e($ctaUrl) ?>" class="site-button site-button-light"><?= e($ctaLabel) ?></a>
                <a href="#about" class="site-button site-button-outline">Learn about the group</a>
            </div>
            <p class="site-hero-note">
                <?= $canRequest
                    ? 'Membership is proof-backed. A group admin reviews each request before you are admitted.'
                    : 'This group is invitation only. Get in touch with the group leadership to ask about joining.' ?>
            </p>
        <?php endif; ?>
    </div>
</section>

<main>
    <section class="site-section" id="about">
        <div class="site-container site-about-grid">
            <div>
                <p class="site-eyebrow">About the group</p>
                <h2>Keep the connection going</h2>
                <div class="site-prose"><?= nl2br(e($about)) ?></div>
            </div>
            <aside class="site-facts">
                <h3>At a glance</h3>
                <dl>
                    <div><dt>Institution</dt><dd><?= e($group['institution'] ?: 'Alumni community') ?></dd></div>
                    <div><dt>Affiliation</dt><dd><?= e($descriptor) ?></dd></div>
                    <?php if (!empty($group['faculty'])): ?><div><dt>Faculty</dt><dd><?= e($group['faculty']) ?></dd></div><?php endif; ?>
                    <?php if (!empty($group['department'])): ?><div><dt>Department</dt><dd><?= e($group['department']) ?></dd></div><?php endif; ?>
                    <?php if (!empty($group['country'])): ?><div><dt>Based in</dt><dd><?= e($group['country']) ?></dd></div><?php endif; ?>
                    <div><dt>Active members</dt><dd><?= (int) $memberCount ?></dd></div>
                </dl>
                <?php if ($showJoin): ?>
                    <a href="<?= e($ctaUrl) ?>" class="site-button site-button-wide"><?= e($ctaLabel) ?></a>
                <?php endif; ?>
            </aside>
        </div>
    </section>

    <?php if ($announcements !== []): ?>
        <section class="site-section site-section-alt" id="news">
            <div class="site-container">
                <div class="site-section-head">
                    <div>
                        <p class="site-eyebrow">Latest news</p>
                        <h2>From the group</h2>
                    </div>
                    <a href="<?= e($loginUrl) ?>" class="site-text-link">Sign in for the full feed</a>
                </div>
                <div class="site-card-grid">
                    <?php foreach ($announcements as $announcement): ?>
                        <article class="site-card">
                            <time datetime="<?= e(date('Y-m-d', strtotime((string) $announcement['created_at']))) ?>">
                                <?= e(short_date($announcement['created_at'])) ?>
                            </time>
                            <h3><?= e($announcement['title']) ?></h3>
                            <p><?= e(str_limit(strip_tags((string) $announcement['body']), 150)) ?></p>
                            <span class="site-byline">By <?= e($announcement['author_name']) ?></span>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($events !== []): ?>
        <section class="site-section" id="events">
            <div class="site-container">
                <div class="site-section-head">
                    <div>
                        <p class="site-eyebrow">What is coming up</p>
                        <h2>Upcoming events</h2>
                    </div>
                    <a href="<?= e($loginUrl) ?>" class="site-text-link">Sign in to RSVP</a>
                </div>
                <div class="site-event-list">
                    <?php foreach ($events as $event): ?>
                        <?php $starts = strtotime((string) $event['starts_at']); ?>
                        <article class="site-event">
                            <div class="site-event-date">
                                <span><?= e(date('M', $starts)) ?></span>
                                <strong><?= e(date('j', $starts)) ?></strong>
                            </div>
                            <div class="site-event-body">
                                <h3><?= e($event['title']) ?></h3>
                                <p class="site-event-meta">
                                    <?= e(date('D, M j · g:i A', $starts)) ?>
                                    <span>·</span>
                                    <?= e($event['location'] ?: 'Venue to be confirmed') ?>
                                </p>
                                <?php if (!empty($event['description'])): ?>
                                    <p><?= e(str_limit(strip_tags((string) $event['description']), 170)) ?></p>
                                <?php endif; ?>
                                <div class="flex flex-wrap" style="gap:14px">
                                    <a href="<?= e($loginUrl) ?>" class="site-text-link">View in Classof</a>
                                    <a href="<?= e(main_url('groups/' . $group['slug'] . '/events/' . $event['id'] . '/calendar')) ?>" class="site-text-link">Add to calendar</a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($exco !== []): ?>
        <section class="site-section site-section-alt" id="leadership">
            <div class="site-container">
                <div class="site-section-head">
                    <div>
                        <p class="site-eyebrow">Group leadership</p>
                        <h2>The team behind the group</h2>
                    </div>
                </div>
                <div class="site-people">
                    <?php foreach (array_slice($exco, 0, 8) as $leader): ?>
                        <div class="site-person">
                            <?= avatar($leader['name'], $leader['avatar'] ?? null, 56) ?>
                            <div>
                                <strong><?= e($leader['name']) ?></strong>
                                <span><?= e($leader['position_title'] ?: role_label($leader['role'])) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section class="site-join" id="contact">
        <div class="site-container site-join-grid">
            <div>
                <p class="site-eyebrow">Stay connected</p>
                <h2><?= $showJoin ? 'Be part of the story' : 'Get in touch' ?></h2>
                <p>
                    <?= $showJoin
                        ? 'Join classmates and former colleagues, keep up with group news and help the community support one another.'
                        : 'This group keeps membership within its own network. Contact the leadership to ask about joining.' ?>
                </p>
                <div class="site-join-actions">
                    <?php if ($showJoin): ?>
                        <a href="<?= e($ctaUrl) ?>" class="site-button site-button-light"><?= e($ctaLabel) ?></a>
                    <?php endif; ?>
                    <a href="<?= e($loginUrl) ?>" class="site-button site-button-outline">Sign in to Classof</a>
                </div>
            </div>
            <div class="site-contact-card">
                <h3>Contact the group</h3>
                <ul>
                    <?php if (!empty($site['contact_email'])): ?>
                        <li><span>Email</span><a href="mailto:<?= e($site['contact_email']) ?>"><?= e($site['contact_email']) ?></a></li>
                    <?php endif; ?>
                    <?php if ($phoneHref !== null): ?>
                        <li><span>Phone</span><a href="<?= e($phoneHref) ?>"><?= e($site['contact_phone']) ?></a></li>
                    <?php endif; ?>
                    <?php if (!empty($site['contact_address'])): ?>
                        <li><span>Address</span><span><?= e($site['contact_address']) ?></span></li>
                    <?php endif; ?>
                    <?php if ($socials === [] && empty($site['contact_email']) && empty($site['contact_phone']) && empty($site['contact_address'])): ?>
                        <li class="site-contact-empty">Contact details will appear here once the group editor adds them.</li>
                    <?php endif; ?>
                </ul>
                <?php if ($socials !== []): ?>
                    <div class="site-socials">
                        <?php foreach ($socials as $label => $url): ?>
                            <a href="<?= e($url) ?>" target="_blank" rel="noopener"><?= e($label) ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="site-container site-footer-inner">
        <div>
            <strong><?= e($group['name']) ?></strong>
            <span><?= e($descriptor) ?><?php if (!empty($group['institution'])): ?> · <?= e($group['institution']) ?><?php endif; ?></span>
        </div>
        <div class="site-footer-links">
            <a href="<?= e($loginUrl) ?>">Member sign in</a>
            <?php if ($showJoin): ?><a href="<?= e($ctaUrl) ?>"><?= e($ctaLabel) ?></a><?php endif; ?>
            <a href="<?= e(main_url()) ?>">Powered by Classof</a>
        </div>
        <span class="site-copyright">&copy; <?= date('Y') ?> <?= e($group['name']) ?>.</span>
    </div>
</footer>
