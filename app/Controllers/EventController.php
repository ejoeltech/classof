<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Guard;
use App\Core\Request;
use App\Models\Event;
use App\Models\Group;

final class EventController extends Controller
{
    public function index(string $slug): void
    {
        [$group, $membership] = $this->groupContext($slug);

        $scope = Request::input('scope') === 'past' ? 'past' : 'upcoming';

        $this->view('events/index', [
            'group' => $group,
            'membership' => $membership,
            'isManager' => Guard::canManage($membership),
            'scope' => $scope,
            'events' => Event::forGroup((int) $group['id'], $scope),
        ]);
    }

    public function store(string $slug): void
    {
        [$group] = $this->groupContext($slug, ['owner', 'admin', 'exco']);

        $data = $this->validate([
            'title' => 'required|min:3|max:180',
            'description' => 'max:5000',
            'location' => 'max:200',
            'starts_at' => 'required|date',
            'ends_at' => 'date',
            'capacity' => 'integer',
            'fee' => 'numeric',
        ]);

        $starts = strtotime((string) $data['starts_at']);

        if ($starts === false) {
            $this->fail(['starts_at' => 'Enter a valid start date and time.']);
        }

        if (($data['ends_at'] ?? '') !== '' && strtotime((string) $data['ends_at']) < $starts) {
            $this->fail(['ends_at' => 'The end time must be after the start time.']);
        }

        $id = Event::create([
            'group_id' => (int) $group['id'],
            'title' => $data['title'],
            'description' => $data['description'] ?: null,
            'location' => $data['location'] ?: null,
            'starts_at' => date('Y-m-d H:i:s', $starts),
            'ends_at' => ($data['ends_at'] ?? '') !== '' ? date('Y-m-d H:i:s', strtotime((string) $data['ends_at'])) : null,
            'capacity' => ($data['capacity'] ?? '') !== '' ? (int) $data['capacity'] : null,
            'fee' => (float) ($data['fee'] ?? 0),
            'created_by' => (int) $this->currentUser()['id'],
        ]);

        $this->audit((int) $group['id'], 'event.created', 'Created the event "' . $data['title'] . '"', 'event', $id);
        $this->flashSuccess('Event published.');
        $this->redirect('groups/' . $slug . '/events/' . $id);
    }

    public function show(string $slug, string $id): void
    {
        [$group, $membership] = $this->groupContext($slug);

        $event = Event::find((int) $id);

        if ($event === null || (int) $event['group_id'] !== (int) $group['id']) {
            \App\Core\Response::abort(404, 'That event does not exist.');
        }

        $myRsvp = Event::rsvpFor((int) $event['id'], (int) user()['id']);
        $isManager = Guard::canManage($membership);

        $this->view('events/show', [
            'group' => $group,
            'membership' => $membership,
            'event' => $event,
            'isManager' => $isManager,
            'myRsvp' => $myRsvp,
            'counts' => Event::rsvpCounts((int) $event['id']),
            'attendees' => $isManager ? Event::attendees((int) $event['id']) : [],
            'payment' => \App\Models\Payment::successFor('event', (int) $event['id'], (int) user()['id']),
        ]);
    }

    public function calendar(string $slug, string $id): void
    {
        $group = Group::findBySlug($slug);

        if ($group === null || ($group['status'] ?? 'active') !== 'active') {
            \App\Core\Response::abort(404, 'That group does not exist.');
        }

        $event = Event::find((int) $id);

        if ($event === null || (int) $event['group_id'] !== (int) $group['id'] || $event['status'] !== 'published') {
            \App\Core\Response::abort(404, 'That event does not exist.');
        }

        if (($group['visibility'] ?? 'public') === 'private') {
            $membership = Guard::membership((int) $group['id']);

            if ($membership === null || $membership['status'] !== 'active') {
                \App\Core\Response::abort(403, 'This event is for group members.');
            }
        }

        $start = strtotime((string) $event['starts_at']) ?: time();
        $end = !empty($event['ends_at']) ? strtotime((string) $event['ends_at']) : $start + 7200;
        $eventUrl = absolute_url('groups/' . $group['slug'] . '/events/' . $event['id']);
        $description = trim(strip_tags((string) ($event['description'] ?? '')));

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Classof//Events//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:classof-event-' . $event['id'] . '@' . (parse_url(main_url(), PHP_URL_HOST) ?: 'classof'),
            'DTSTAMP:' . gmdate('Ymd\THis\Z'),
            'DTSTART:' . gmdate('Ymd\THis\Z', $start),
            'DTEND:' . gmdate('Ymd\THis\Z', $end),
            'SUMMARY:' . $this->icsEscape((string) $event['title']),
            'DESCRIPTION:' . $this->icsEscape($description . ($description !== '' ? "\n\n" : '') . $eventUrl),
            'LOCATION:' . $this->icsEscape((string) ($event['location'] ?? '')),
            'URL:' . $this->icsEscape($eventUrl),
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="classof-event-' . (int) $event['id'] . '.ics"');

        foreach ($lines as $line) {
            echo $this->icsFold($line) . "\r\n";
        }

        exit;
    }

    public function rsvp(string $slug, string $id): void
    {
        [$group] = $this->groupContext($slug);

        $event = Event::find((int) $id);

        if ($event === null || (int) $event['group_id'] !== (int) $group['id']) {
            \App\Core\Response::abort(404, 'That event does not exist.');
        }

        $status = Request::string('status', 'going');

        if (!in_array($status, ['going', 'maybe', 'declined'], true)) {
            $this->flashError('Choose a valid RSVP option.');
            $this->redirect('groups/' . $slug . '/events/' . $id);
        }

        $guests = min(10, max(0, Request::int('guests')));

        $existing = Event::rsvpFor((int) $event['id'], (int) user()['id']);

        if ($status === 'going'
            && !empty($event['capacity'])
            && ($existing['status'] ?? '') !== 'going'
            && Event::rsvpCounts((int) $event['id'])['going'] >= (int) $event['capacity']) {
            $this->flashError('This event has reached its capacity. Contact an organiser if you still plan to attend.');
            $this->redirect('groups/' . $slug . '/events/' . $id);
        }

        Event::setRsvp((int) $event['id'], (int) user()['id'], $status, $guests);

        $this->flashSuccess('Your RSVP has been saved.');
        $this->redirect('groups/' . $slug . '/events/' . $id);
    }

    public function destroy(string $slug, string $id): void
    {
        [$group] = $this->groupContext($slug, ['owner', 'admin']);

        $event = Event::find((int) $id);

        if ($event === null || (int) $event['group_id'] !== (int) $group['id']) {
            \App\Core\Response::abort(404, 'That event does not exist.');
        }

        Database::delete('events', ['id' => (int) $event['id']]);
        $this->audit((int) $group['id'], 'event.deleted', 'Deleted the event "' . $event['title'] . '"', 'event', (int) $event['id']);

        $this->flashSuccess('Event deleted.');
        $this->redirect('groups/' . $slug . '/events');
    }

    private function icsEscape(string $value): string
    {
        $value = str_replace(["\r\n", "\r", "\n"], '\n', $value);

        return str_replace(['\\', ';', ','], ['\\\\', '\;', '\,'], $value);
    }

    private function icsFold(string $line): string
    {
        if (strlen($line) <= 75) {
            return $line;
        }

        $output = '';

        while (strlen($line) > 75) {
            $output .= substr($line, 0, 75) . "\r\n ";
            $line = substr($line, 75);
        }

        return $output . $line;
    }
}
