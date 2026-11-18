<?php $title = 'Alumni group manager with welfare & elections'; ?>

<section class="hero">
    <div class="container hero-grid">
        <div>
            <p class="eyebrow" style="color:var(--forest-700)">Primary · Secondary · University</p>
            <h1>Your class never <em>really</em> ends.</h1>
            <p class="hero-lead">
                From an institution-wide alumni association to your exact class, department or faculty —
                Classof keeps every affiliation connected, with proof-backed membership, dues, welfare,
                a member-owned cooperative, and elections everyone can trust.
            </p>
            <div class="pill-row" style="margin-bottom:24px">
                <span class="badge badge-neutral">Institution-wide</span>
                <span class="badge badge-neutral">Class / Set</span>
                <span class="badge badge-neutral">Department</span>
                <span class="badge badge-neutral">Faculty</span>
                <span class="badge badge-neutral">Association</span>
            </div>
            <div class="hero-actions">
                <a href="<?= url('register') ?>" class="btn btn-primary btn-lg">Find your alumni group <?= icon('arrow-right', 17) ?></a>
                <a href="<?= url('explore') ?>" class="btn btn-outline btn-lg">Explore groups</a>
            </div>
            <div class="hero-proof">
                <div class="hero-proof-avatars">
                    <?php foreach (['Amara Okafor', 'Tunde Bakare', 'Chioma Nwosu', 'Emeka Obi'] as $i => $person): ?>
                        <span class="avatar avatar-initials" style="width:32px;height:32px;font-size:11px"><?= e(initials($person)) ?></span>
                    <?php endforeach; ?>
                </div>
                <span><strong><?= number_format($stats['members']) ?></strong> alumni already organising on Classof</span>
            </div>
        </div>

        <div class="hero-visual">
            <div class="hero-card">
                <div class="hero-card-cover"></div>
                <div class="hero-card-body">
                    <div class="hero-card-logo">K</div>
                    <h3>King's College — Class of 2010</h3>
                    <p class="hero-card-sub">Lagoon House · Lagos, Nigeria</p>

                    <div class="hero-mini-stats">
                        <div class="hero-mini-stat"><span>Members</span><strong>248</strong></div>
                        <div class="hero-mini-stat"><span>Dues paid</span><strong>82%</strong></div>
                        <div class="hero-mini-stat"><span>Welfare</span><strong>₦1.2m</strong></div>
                    </div>

                    <div class="hero-progress-label"><span>Medical support for a classmate</span><strong>68%</strong></div>
                    <div class="progress"><span style="width:68%"></span></div>

                    <div class="hero-ballot">
                        <span class="hero-ballot-icon"><?= icon('vote', 17) ?></span>
                        <div class="hero-ballot-text">
                            <strong>2026 Exco Election</strong>
                            <span>Voting closes in 3 days · 1 member, 1 vote</span>
                        </div>
                        <span class="badge badge-success">Open</span>
                    </div>
                </div>
            </div>

            <div class="hero-badge-float">
                <?= icon('shield', 20) ?>
                <div>
                    <strong>Secret ballot</strong>
                    <span>Every vote is confidential</span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="stats-band">
    <div class="container stats-band-inner">
        <div><strong><?= number_format($stats['groups']) ?></strong><span>Alumni groups</span></div>
        <div><strong><?= number_format($stats['members']) ?></strong><span>Active members</span></div>
        <div><strong><?= money($stats['welfare']) ?></strong><span>Mobilised for welfare</span></div>
    </div>
</section>

<section class="section" id="features">
    <div class="container">
        <div class="section-head-center">
            <p class="eyebrow" style="color:var(--forest-700)">Everything in one place</p>
            <h2>Built for the way alumni groups actually work</h2>
            <p>No spreadsheets, no WhatsApp polling, no arguments over the books. Just one clean home for your class.</p>
        </div>

        <div class="feature-grid">
            <article class="feature-card">
                <span class="feature-icon"><?= icon('users', 21) ?></span>
                <h3>A living member directory</h3>
                <p>Profiles, occupations, locations and membership numbers. Find the classmate you have been looking for.</p>
            </article>
            <article class="feature-card">
                <span class="feature-icon"><?= icon('wallet', 21) ?></span>
                <h3>Dues without the chasing</h3>
                <p>Create a dues cycle, track who has paid, and see collected totals update automatically.</p>
            </article>
            <article class="feature-card">
                <span class="feature-icon"><?= icon('heart', 21) ?></span>
                <h3>Welfare with a ledger</h3>
                <p>Open verified cases for bereavement, medical or education support. Every contribution is recorded.</p>
            </article>
            <article class="feature-card">
                <span class="feature-icon"><?= icon('vote', 21) ?></span>
                <h3>Exco elections that hold</h3>
                <p>Positions, manifestos and a secret ballot with one vote per member, per position. Results are auditable.</p>
            </article>
            <article class="feature-card">
                <span class="feature-icon"><?= icon('calendar', 21) ?></span>
                <h3>Events &amp; RSVPs</h3>
                <p>Reunions, homecomings and webinars with capacity, guest counts and a live attendee list.</p>
            </article>
            <article class="feature-card">
                <span class="feature-icon"><?= icon('chart', 21) ?></span>
                <h3>A treasury you can trust</h3>
                <p>Dues, welfare and event income in one ledger — with CSV exports for the AGM finance report.</p>
            </article>
            <article class="feature-card">
                <span class="feature-icon"><?= icon('card', 21) ?></span>
                <h3>A real cooperative society</h3>
                <p>Shares, savings, guaranteed loans, fines, meetings and dividends — with a live member statement.</p>
            </article>
            <article class="feature-card">
                <span class="feature-icon"><?= icon('users', 21) ?></span>
                <h3>One profile, many affiliations</h3>
                <p>Be part of your institution association, class set, department, faculty and old boys body — all from one account.</p>
            </article>
            <article class="feature-card">
                <span class="feature-icon"><?= icon('shield', 21) ?></span>
                <h3>Proof-backed membership</h3>
                <p>Self-service join requests include an ID, certificate or transcript and stay pending until an admin verifies them.</p>
            </article>
        </div>
    </div>
</section>

<section class="section section-alt" id="how">
    <div class="container">
        <div class="section-head-center">
            <p class="eyebrow" style="color:var(--forest-700)">From first invite to first election</p>
            <h2>Three steps to an organised class</h2>
        </div>

        <div class="steps">
            <div class="step">
                <span class="step-num">1</span>
                <h3>Find your alumni group</h3>
                <p>Add your institution, programme and year. Classof shows the institution, class and association groups that match, then you request to join.</p>
            </div>
            <div class="step">
                <span class="step-num">2</span>
                <h3>Invite, verify, approve</h3>
                <p>Share invite links, or review proof-backed join requests and approve the classmates who belong.</p>
            </div>
            <div class="step">
                <span class="step-num">3</span>
                <h3>Collect dues, support members, elect leaders</h3>
                <p>Run dues cycles and welfare cases, then hold a transparent election when it is time to hand over.</p>
            </div>
        </div>
    </div>
</section>

<?php if ($groups !== []): ?>
<section class="section">
    <div class="container">
        <div class="between mb-3">
            <div>
                <h2>Groups already on Classof</h2>
                <p class="muted small mt-1">Find your class and request to join.</p>
            </div>
            <a href="<?= url('explore') ?>" class="btn btn-outline btn-sm">View all <?= icon('arrow-right', 15) ?></a>
        </div>

        <div class="group-preview-grid">
            <?php foreach ($groups as $group): ?>
                <a class="group-preview" href="<?= url('join/' . $group['slug']) ?>">
                    <div class="group-preview-cover" style="--cover: <?= e($group['cover_color'] ?: '#0b6e4f') ?>"></div>
                    <div class="group-preview-body">
                        <div class="group-preview-logo"><?= e(mb_substr((string) $group['name'], 0, 1)) ?></div>
                        <h3><?= e($group['name']) ?></h3>
                        <p><?= e(App\Models\Group::scopeLabel($group['scope'] ?? 'class')) ?> · <?= e(App\Models\Group::descriptor($group)) ?></p>
                        <div class="group-preview-meta">
                            <span><?= icon('users', 14) ?> <?= (int) $group['member_count'] ?> members</span>
                            <span class="badge badge-neutral"><?= e($group['country'] ?: 'Global') ?></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="cta-band">
    <div class="container cta-inner">
        <div>
            <h2>Bring your class back together.</h2>
            <p>Set up your alumni group in minutes. Free to start, with dues, welfare and elections from day one.</p>
        </div>
        <a href="<?= url('register') ?>" class="btn btn-gold btn-lg">Get started free <?= icon('arrow-right', 17) ?></a>
    </div>
</section>
