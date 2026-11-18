<?php
$title = 'Group website';
require BASE_PATH . '/app/Views/partials/group-header.php';
$accent = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $site['accent_color']) === 1 ? $site['accent_color'] : '#0B6E4F';
$domain = (string) config('site.domain', '');
?>
<div class="page-head">
    <div>
        <h1>Group website</h1>
        <p>A simple public landing page for this group, kept in sync with its news, events and leadership.</p>
    </div>
    <div class="page-head-actions">
        <a href="<?= e($previewUrl) ?>" class="btn btn-outline" target="_blank" rel="noopener">Preview</a>
        <a href="<?= e($siteUrl) ?>" class="btn btn-ghost" target="_blank" rel="noopener">Open site</a>
        <?php if ($isManager): ?>
            <a href="<?= url('groups/' . $group['slug'] . '/settings') ?>" class="btn btn-ghost">Group settings</a>
        <?php else: ?>
            <a href="<?= url('dashboard') ?>" class="btn btn-ghost">Dashboard</a>
        <?php endif; ?>
    </div>
</div>

<div class="callout mb-3">
    <?= icon('link', 17) ?>
    <div>
        Public address:
        <strong><?= e($siteUrl) ?></strong>
        <?php if ($domain !== ''): ?>
            <span class="muted small">· Requests to this subdomain are served by Classof. Wildcard DNS and an Apache vhost for <code>*.<?= e($domain) ?></code> are required in production.</span>
        <?php else: ?>
            <span class="muted small">· <code>SITE_DOMAIN</code> is not set, so the path fallback is used.</span>
        <?php endif; ?>
    </div>
</div>

<form method="post" action="<?= url('groups/' . $group['slug'] . '/site') ?>" enctype="multipart/form-data" class="site-editor-form">
    <?= csrf_field() ?>
    <div class="split-main">
        <div class="stack">
            <section class="card">
                <div class="card-header">
                    <div>
                        <h2>Page content</h2>
                        <p class="sub">The headline, short introduction and supporting text visitors see first.</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="field">
                        <label for="headline">Headline</label>
                        <input type="text" id="headline" name="headline" maxlength="160" required
                               value="<?= e(old('headline', $site['headline'] ?? $group['name'])) ?>"
                               class="<?= isset($errors['headline']) ? 'is-invalid' : '' ?>">
                        <?php if (isset($errors['headline'])): ?><span class="error-text"><?= e($errors['headline']) ?></span><?php endif; ?>
                        <span class="hint">Keep it short. The group name is a good default.</span>
                    </div>

                    <div class="field">
                        <label for="tagline">Tagline</label>
                        <input type="text" id="tagline" name="tagline" maxlength="255"
                               value="<?= e(old('tagline', $site['tagline'] ?? '')) ?>"
                               placeholder="Keeping the Class of 2010 connected.">
                        <?php if (isset($errors['tagline'])): ?><span class="error-text"><?= e($errors['tagline']) ?></span><?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="about">About the group</label>
                        <textarea id="about" name="about" rows="8" maxlength="4000"><?= e(old('about', $site['about'] ?? '')) ?></textarea>
                        <?php if (isset($errors['about'])): ?><span class="error-text"><?= e($errors['about']) ?></span><?php endif; ?>
                        <span class="hint">Plain text. Blank lines become paragraphs.</span>
                    </div>

                    <div class="form-grid">
                        <div class="field">
                            <label for="accent_color">Accent colour</label>
                            <input type="color" id="accent_color" name="accent_color" value="<?= e(old('accent_color', $accent)) ?>">
                            <?php if (isset($errors['accent_color'])): ?><span class="error-text"><?= e($errors['accent_color']) ?></span><?php endif; ?>
                            <span class="hint">Used for buttons, labels and the hero gradient.</span>
                        </div>

                        <div class="field">
                            <label for="hero_image">Hero image</label>
                            <input type="file" id="hero_image" name="hero_image" accept="image/jpeg,image/png,image/webp,image/gif">
                            <?php if (isset($errors['hero_image'])): ?><span class="error-text"><?= e($errors['hero_image']) ?></span><?php endif; ?>
                            <?php if (!empty($site['hero_image'])): ?>
                                <label class="checkbox mt-2">
                                    <input type="checkbox" name="remove_hero" value="1">
                                    <span>Remove the current image</span>
                                </label>
                                <img src="<?= e(upload_url($site['hero_image'])) ?>" alt="" style="margin-top:10px;max-width:100%;max-height:150px;border-radius:10px;object-fit:cover">
                            <?php endif; ?>
                            <span class="hint">Optional. JPEG, PNG, WebP or GIF up to 4MB. A gradient is used without an image.</span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-header">
                    <div>
                        <h2>Contact &amp; social links</h2>
                        <p class="sub">Optional details shown in the contact panel and footer.</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-grid">
                        <div class="field">
                            <label for="contact_email">Email</label>
                            <input type="email" id="contact_email" name="contact_email" maxlength="190" value="<?= e(old('contact_email', $site['contact_email'] ?? '')) ?>" placeholder="alumni@example.com">
                            <?php if (isset($errors['contact_email'])): ?><span class="error-text"><?= e($errors['contact_email']) ?></span><?php endif; ?>
                        </div>
                        <div class="field">
                            <label for="contact_phone">Phone</label>
                            <input type="tel" id="contact_phone" name="contact_phone" maxlength="40" value="<?= e(old('contact_phone', $site['contact_phone'] ?? '')) ?>" placeholder="+234 801 234 5678">
                            <?php if (isset($errors['contact_phone'])): ?><span class="error-text"><?= e($errors['contact_phone']) ?></span><?php endif; ?>
                        </div>
                        <div class="field span-2">
                            <label for="contact_address">Address</label>
                            <input type="text" id="contact_address" name="contact_address" maxlength="190" value="<?= e(old('contact_address', $site['contact_address'] ?? '')) ?>" placeholder="Group secretariat or mailing address">
                            <?php if (isset($errors['contact_address'])): ?><span class="error-text"><?= e($errors['contact_address']) ?></span><?php endif; ?>
                        </div>
                    </div>

                    <div class="divider"></div>
                    <p class="tiny muted" style="text-transform:uppercase;letter-spacing:.08em;font-weight:700">Social links</p>
                    <div class="form-grid">
                        <?php
                        $socialFields = [
                            'facebook_url' => 'Facebook',
                            'instagram_url' => 'Instagram',
                            'x_url' => 'X / Twitter',
                            'linkedin_url' => 'LinkedIn',
                            'youtube_url' => 'YouTube',
                            'whatsapp_url' => 'WhatsApp',
                        ];
                        ?>
                        <?php foreach ($socialFields as $field => $label): ?>
                            <div class="field">
                                <label for="<?= e($field) ?>"><?= e($label) ?></label>
                                <input type="url" id="<?= e($field) ?>" name="<?= e($field) ?>" maxlength="255" value="<?= e(old($field, $site[$field] ?? '')) ?>" placeholder="https://…">
                                <?php if (isset($errors[$field])): ?><span class="error-text"><?= e($errors[$field]) ?></span><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-header">
                    <div>
                        <h2>Draft the copy</h2>
                        <p class="sub">Generate a first draft from the group’s existing profile, then edit it.</p>
                    </div>
                    <span class="badge badge-<?= $aiConfigured ? 'success' : 'neutral' ?>"><?= $aiConfigured ? 'AI ready' : 'Group data' ?></span>
                </div>
                <div class="card-body">
                    <p class="muted small" style="margin-top:0">
                        <?= $aiConfigured
                            ? 'A configured AI endpoint writes a short headline, tagline and about section. Review the result before publishing.'
                            : 'No AI key is configured, so a structured draft is generated from the group name, institution and description. Add AI_API_KEY to use an AI model.' ?>
                    </p>
                    <button type="submit" name="action" value="draft" class="btn btn-outline" formnovalidate>
                        <?= icon('edit', 15) ?> <?= $aiConfigured ? 'Draft with AI' : 'Generate draft from group data' ?>
                    </button>
                    <span class="tiny muted" style="margin-left:10px">This replaces the headline, tagline and about text only.</span>
                </div>
            </section>
        </div>

        <aside class="stack">
            <section class="card">
                <div class="card-header"><h2>Publishing</h2></div>
                <div class="card-body">
                    <?php if ($isManager): ?>
                        <div class="field">
                            <label for="subdomain">Subdomain</label>
                            <input type="text" id="subdomain" name="subdomain" maxlength="63" required
                                   value="<?= e(old('subdomain', $site['subdomain'])) ?>"
                                   class="<?= isset($errors['subdomain']) ? 'is-invalid' : '' ?>">
                            <?php if (isset($errors['subdomain'])): ?><span class="error-text"><?= e($errors['subdomain']) ?></span><?php endif; ?>
                            <span class="hint"><?= $domain !== '' ? 'https://' . e($site['subdomain']) . '.' . e($domain) : 'Used in the public site path' ?> · lowercase letters, numbers and hyphens.</span>
                        </div>
                        <div class="field">
                            <label for="status">Status</label>
                            <select id="status" name="status" class="select">
                                <option value="draft" <?= old('status', $site['status']) === 'draft' ? 'selected' : '' ?>>Draft — hidden from the public</option>
                                <option value="published" <?= old('status', $site['status']) === 'published' ? 'selected' : '' ?>>Published — visible to everyone</option>
                            </select>
                            <?php if (isset($errors['status'])): ?><span class="error-text"><?= e($errors['status']) ?></span><?php endif; ?>
                        </div>
                        <?php if (($group['visibility'] ?? 'public') === 'private'): ?>
                            <div class="callout" style="margin-bottom:0">
                                <?= icon('lock', 16) ?>
                                <div>This group is private. Publishing its website makes the page and its selected sections public, even though the group directory stays hidden.</div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="small muted" style="margin-top:0">Only a group owner or admin can change the address or publish the page.</p>
                        <dl class="tiny" style="display:grid;gap:8px;margin:0">
                            <div><dt class="muted">Address</dt><dd style="margin:0;font-weight:600"><?= e($site['subdomain']) ?></dd></div>
                            <div><dt class="muted">Status</dt><dd style="margin:0;font-weight:600"><?= e(ucfirst($site['status'])) ?></dd></div>
                        </dl>
                    <?php endif; ?>

                    <div class="divider"></div>
                    <p class="tiny muted" style="margin:0">Preview is private. Published pages are public and do not require a Classof account.</p>
                </div>
            </section>

            <section class="card">
                <div class="card-header"><h2>Page sections</h2></div>
                <div class="card-body stack-sm">
                    <?php
                    $sectionFields = [
                        'show_announcements' => ['Latest news', 'Show the three newest announcement titles and excerpts.'],
                        'show_events' => ['Upcoming events', 'Show the next three published events.'],
                        'show_leaders' => ['Leadership', 'Show the owner, admins and exco members.'],
                        'show_stats' => ['Member stats', 'Show member and update counts in the hero.'],
                        'show_join' => ['Join call-to-action', 'Show the request-to-join button and CTA section.'],
                    ];
                    ?>
                    <?php foreach ($sectionFields as $field => [$label, $hint]): ?>
                        <div>
                            <input type="hidden" name="<?= e($field) ?>" value="0">
                            <label class="checkbox">
                                <input type="checkbox" name="<?= e($field) ?>" value="1" <?= (string) old($field, $site[$field] ?? '1') === '1' ? 'checked' : '' ?>>
                                <span><?= e($label) ?></span>
                            </label>
                            <span class="hint" style="margin-left:27px"><?= e($hint) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="card">
                <div class="card-header"><h2>Assigned editor</h2></div>
                <div class="card-body">
                    <p class="small muted" style="margin-top:0">A group admin can edit this page. You can also assign a registered member or contracted professional to manage the page without giving them group admin rights.</p>
                    <?php if ($isManager): ?>
                        <div class="field">
                            <label for="editor_email">Editor email</label>
                            <input type="email" id="editor_email" name="editor_email" maxlength="190" value="<?= e(old('editor_email', $editor['email'] ?? '')) ?>" placeholder="editor@example.com">
                            <?php if (isset($errors['editor_email'])): ?><span class="error-text"><?= e($errors['editor_email']) ?></span><?php endif; ?>
                            <span class="hint">Leave blank to remove the assigned editor.</span>
                        </div>
                    <?php elseif ($editor !== null): ?>
                        <p class="small" style="margin:0"><strong><?= e($editor['name']) ?></strong><br><span class="muted"><?= e($editor['email']) ?></span></p>
                    <?php endif; ?>

                    <?php if ($editor !== null): ?>
                        <div class="callout info mt-2" style="margin-bottom:0">
                            <?= icon('user', 16) ?>
                            <div><strong><?= e($editor['name']) ?></strong> can edit this website. Group admins can still edit it too.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="card">
                <div class="card-body stack-sm">
                    <button type="submit" name="action" value="save" class="btn btn-primary btn-block">Save website</button>
                    <a href="<?= e($previewUrl) ?>" class="btn btn-outline btn-block" target="_blank" rel="noopener">Preview page</a>
                    <a href="<?= e($siteUrl) ?>" class="btn btn-ghost btn-block" target="_blank" rel="noopener">Open public site</a>
                </div>
            </section>
        </aside>
    </div>
</form>
