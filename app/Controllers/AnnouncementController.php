<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Guard;
use App\Models\Announcement;
use App\Services\WhatsAppService;

final class AnnouncementController extends Controller
{
    public function index(string $slug): void
    {
        [$group, $membership] = $this->groupContext($slug);

        $this->view('announcements/index', [
            'group' => $group,
            'membership' => $membership,
            'isManager' => Guard::canManage($membership),
            'canPost' => in_array($membership['role'], ['owner', 'admin', 'exco'], true),
            'announcements' => Announcement::forGroup((int) $group['id'], 100),
        ]);
    }

    public function store(string $slug): void
    {
        [$group, $membership] = $this->groupContext($slug, ['owner', 'admin', 'exco']);

        $data = $this->validate([
            'title' => 'required|min:3|max:180',
            'body' => 'required|min:5|max:20000',
        ]);

        $id = Announcement::create([
            'group_id' => (int) $group['id'],
            'user_id' => (int) $this->currentUser()['id'],
            'title' => $data['title'],
            'body' => $data['body'],
            'pinned' => \App\Core\Request::boolean('pinned'),
        ]);

        $queued = WhatsAppService::queueAnnouncement(
            ['id' => $id, 'title' => $data['title'], 'body' => $data['body']],
            $group,
            (string) $this->currentUser()['name']
        );

        $this->audit((int) $group['id'], 'announcement.created', 'Published "' . $data['title'] . '"', 'announcement', $id);
        $this->flashSuccess($queued > 0
            ? 'Announcement published. WhatsApp queued for ' . $queued . ' opted-in member(s).'
            : 'Announcement published.');
        $this->redirect('groups/' . $slug . '/announcements');
    }

    public function destroy(string $slug, string $id): void
    {
        [$group, $membership] = $this->groupContext($slug);

        $announcement = Database::fetch(
            'SELECT * FROM announcements WHERE id = ? AND group_id = ? LIMIT 1',
            [(int) $id, (int) $group['id']]
        );

        if ($announcement === null) {
            \App\Core\Response::abort(404, 'That announcement does not exist.');
        }

        $isAuthor = (int) $announcement['user_id'] === (int) user()['id'];

        if (!Guard::canManage($membership) && !$isAuthor) {
            \App\Core\Response::abort(403, 'You cannot delete this announcement.');
        }

        Database::delete('announcements', ['id' => (int) $announcement['id']]);
        $this->audit((int) $group['id'], 'announcement.deleted', 'Deleted "' . $announcement['title'] . '"', 'announcement', (int) $announcement['id']);

        $this->flashSuccess('Announcement deleted.');
        $this->redirect('groups/' . $slug . '/announcements');
    }
}
