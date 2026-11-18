<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Guard;
use App\Core\Request;
use App\Core\Response;
use App\Models\Announcement;
use App\Models\Event;
use App\Models\Group;
use App\Models\GroupSite;
use App\Models\Membership;
use App\Models\User;
use App\Services\SiteContentService;
use App\Services\Uploader;

final class GroupSiteController extends Controller
{
    public function showByHost(): void
    {
        if (!defined('GROUP_SITE_SUBDOMAIN')) {
            Response::abort(404);
        }

        $this->show((string) GROUP_SITE_SUBDOMAIN);
    }

    public function show(string $subdomain): void
    {
        $site = GroupSite::findBySubdomain(GroupSite::normalizeSubdomain($subdomain));

        if ($site === null) {
            Response::abort(404, 'That group website does not exist.');
        }

        $group = Group::find((int) $site['group_id']);

        if ($group === null || ($group['status'] ?? 'active') !== 'active' || $site['status'] !== 'published') {
            Response::abort(404, 'That group website is not available.');
        }

        $this->renderPublic($group, $site);
    }

    public function preview(string $slug): void
    {
        [$group, $site] = $this->editorContext($slug);
        header('X-Robots-Tag: noindex, nofollow');

        $this->renderPublic($group, $site, true);
    }

    public function edit(string $slug): void
    {
        [$group, $site, $membership, $isManager] = $this->editorContext($slug);
        $editor = !empty($site['editor_user_id']) ? User::find((int) $site['editor_user_id']) : null;

        $this->view('sites/edit', [
            'group' => $group,
            'site' => $site,
            'membership' => $membership,
            'isManager' => $isManager,
            'editor' => $editor,
            'aiConfigured' => SiteContentService::aiConfigured(),
            'siteUrl' => group_site_url((string) $site['subdomain']),
            'previewUrl' => url('groups/' . $group['slug'] . '/site/preview'),
        ]);
    }

    public function update(string $slug): void
    {
        [$group, $site, $membership, $isManager] = $this->editorContext($slug);

        if (Request::string('action') === 'draft') {
            $this->draftContent($group, $site);

            return;
        }

        $data = $this->validate([
            'headline' => 'required|min:3|max:160',
            'tagline' => 'max:255',
            'about' => 'max:4000',
            'accent_color' => 'required|max:20',
            'contact_email' => 'email|max:190',
            'contact_phone' => 'max:40',
            'contact_address' => 'max:190',
            'facebook_url' => 'url|max:255',
            'instagram_url' => 'url|max:255',
            'x_url' => 'url|max:255',
            'linkedin_url' => 'url|max:255',
            'youtube_url' => 'url|max:255',
            'whatsapp_url' => 'url|max:255',
            'editor_email' => 'email|max:190',
        ]);

        $accent = (string) $data['accent_color'];

        if (preg_match('/^#[0-9a-fA-F]{6}$/', $accent) !== 1) {
            $this->fail(['accent_color' => 'Choose a six-digit hex colour such as #0B6E4F.']);
        }

        $socialFields = ['facebook_url', 'instagram_url', 'x_url', 'linkedin_url', 'youtube_url', 'whatsapp_url'];

        foreach ($socialFields as $field) {
            $url = $this->nullable($data[$field]);

            if ($url !== null && preg_match('#^https?://#i', $url) !== 1) {
                $this->fail([$field => 'Enter a full http:// or https:// link.']);
            }
        }

        $update = [
            'headline' => trim((string) $data['headline']),
            'tagline' => $this->nullable($data['tagline']),
            'about' => $this->nullable($data['about']),
            'accent_color' => $accent,
            'contact_email' => $this->nullable($data['contact_email']),
            'contact_phone' => $this->nullable($data['contact_phone']),
            'contact_address' => $this->nullable($data['contact_address']),
            'facebook_url' => $this->nullable($data['facebook_url']),
            'instagram_url' => $this->nullable($data['instagram_url']),
            'x_url' => $this->nullable($data['x_url']),
            'linkedin_url' => $this->nullable($data['linkedin_url']),
            'youtube_url' => $this->nullable($data['youtube_url']),
            'whatsapp_url' => $this->nullable($data['whatsapp_url']),
            'show_announcements' => Request::boolean('show_announcements') ? 1 : 0,
            'show_events' => Request::boolean('show_events') ? 1 : 0,
            'show_leaders' => Request::boolean('show_leaders') ? 1 : 0,
            'show_stats' => Request::boolean('show_stats') ? 1 : 0,
            'show_join' => Request::boolean('show_join') ? 1 : 0,
        ];

        if ($isManager) {
            $subdomain = GroupSite::normalizeSubdomain(Request::string('subdomain'));

            if (!GroupSite::validSubdomain($subdomain)) {
                $this->fail(['subdomain' => 'Use 1-63 lowercase letters, numbers or hyphens, starting and ending with a letter or number.']);
            }

            if (GroupSite::subdomainTaken($subdomain, (int) $site['id'])) {
                $this->fail(['subdomain' => 'That subdomain is already used by another group.']);
            }

            $status = Request::string('status');

            if (!in_array($status, ['draft', 'published'], true)) {
                $this->fail(['status' => 'Choose draft or published.']);
            }

            $update['subdomain'] = $subdomain;
            $update['status'] = $status;
            $update['published_at'] = $status === 'published'
                ? ($site['published_at'] ?? date('Y-m-d H:i:s'))
                : $site['published_at'];

            $editorEmail = strtolower(trim((string) $data['editor_email']));

            if ($editorEmail === '') {
                $update['editor_user_id'] = null;
            } else {
                $editor = User::findByEmail($editorEmail);

                if ($editor === null || $editor['status'] !== 'active') {
                    $this->fail(['editor_email' => 'That email does not belong to an active Classof member.']);
                }

                $update['editor_user_id'] = (int) $editor['id'];
            }
        }

        $hero = Request::file('hero_image');

        if ($hero !== null) {
            try {
                $update['hero_image'] = Uploader::image($hero, 'site');
                Uploader::delete($site['hero_image'] ?? null);
            } catch (\RuntimeException $e) {
                $this->fail(['hero_image' => $e->getMessage()]);
            }
        } elseif (Request::boolean('remove_hero')) {
            Uploader::delete($site['hero_image'] ?? null);
            $update['hero_image'] = null;
        }

        GroupSite::update((int) $site['id'], $update);
        $this->audit((int) $group['id'], 'group.site_updated', 'Updated the group website', 'group_site', (int) $site['id']);
        $this->flashSuccess('Group website saved.');
        $this->redirect('groups/' . $group['slug'] . '/site');
    }

    /** @param array<string, mixed> $group @param array<string, mixed> $site */
    private function draftContent(array $group, array $site): void
    {
        $draft = SiteContentService::draft($group, Membership::countActive((int) $group['id']));

        GroupSite::update((int) $site['id'], [
            'headline' => $draft['headline'],
            'tagline' => $draft['tagline'],
            'about' => $draft['about'],
        ]);

        $message = match ($draft['source']) {
            'ai' => 'Website copy drafted with AI. Review it before publishing.',
            'template_fallback' => 'The AI request failed, so a structured draft was generated from your group data.',
            default => 'Website copy drafted from your group data. Review it before publishing.',
        };

        $this->audit((int) $group['id'], 'group.site_drafted', 'Generated website draft copy (' . $draft['source'] . ')', 'group_site', (int) $site['id']);
        $this->flashSuccess($message);
        $this->redirect('groups/' . $group['slug'] . '/site');
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>, 2: array<string, mixed>|null, 3: bool}
     */
    private function editorContext(string $slug): array
    {
        $group = $this->findGroup($slug);
        $site = GroupSite::forGroup($group);
        $membership = Guard::membership((int) $group['id']);
        $isManager = Guard::canManage($membership) || is_admin();
        $isEditor = !empty($site['editor_user_id']) && (int) $site['editor_user_id'] === (int) Auth::id();

        if (!$isManager && !$isEditor) {
            Response::abort(403, 'Only a group admin or the assigned website editor can manage this website.');
        }

        return [$group, $site, $membership, $isManager];
    }

    /** @param array<string, mixed> $group @param array<string, mixed> $site */
    private function renderPublic(array $group, array $site, bool $preview = false): void
    {
        if (!$preview && $site['status'] !== 'published') {
            Response::abort(404, 'That group website is not published yet.');
        }

        $this->view('sites/show', [
            'group' => $group,
            'site' => $site,
            'preview' => $preview,
            'memberCount' => Membership::countActive((int) $group['id']),
            'stats' => Group::stats((int) $group['id']),
            'announcements' => !empty($site['show_announcements']) ? Announcement::forGroup((int) $group['id'], 3) : [],
            'events' => !empty($site['show_events']) ? Event::forGroup((int) $group['id'], 'upcoming', 3) : [],
            'exco' => !empty($site['show_leaders']) ? Membership::exco((int) $group['id']) : [],
        ], 'layouts/site');
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
