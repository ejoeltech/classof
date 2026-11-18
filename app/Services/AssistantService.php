<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Models\Announcement;
use App\Models\Event;
use App\Models\Group;
use App\Models\Membership;
use RuntimeException;

final class AssistantService
{
    private const HISTORY_LIMIT = 12;
    private const RATE_LIMIT = 30;
    private const RATE_WINDOW = 600;

    /** @return array<int, string> */
    public static function groupSuggestions(): array
    {
        return [
            'How do I join this group?',
            'What events are coming up?',
            'Who are the group leaders?',
            'How can I contact the group?',
        ];
    }

    /** @return array<int, string> */
    public static function platformSuggestions(): array
    {
        return [
            'How do I join a group?',
            'How do dues work?',
            'What can I do in the co-op?',
            'How do I edit a group website?',
        ];
    }

    public static function aiConfigured(): bool
    {
        return trim((string) config('ai.api_key', '')) !== '';
    }

    /**
     * Public information only: this context is safe for a group landing page.
     *
     * @param array<string, mixed> $group
     * @param array<string, mixed> $site
     * @return array<string, mixed>
     */
    public static function groupSiteContext(array $group, array $site): array
    {
        $groupId = (int) $group['id'];
        $announcements = !empty($site['show_announcements']) ? Announcement::forGroup($groupId, 3) : [];
        $events = !empty($site['show_events']) ? Event::forGroup($groupId, 'upcoming', 3) : [];
        $exco = !empty($site['show_leaders']) ? Membership::exco($groupId) : [];

        return [
            'kind' => 'group_website',
            'group' => [
                'name' => $group['name'] ?? '',
                'institution' => $group['institution'] ?? '',
                'scope' => Group::scopeLabel($group['scope'] ?? 'class'),
                'affiliation' => Group::descriptor($group),
                'faculty' => $group['faculty'] ?? '',
                'department' => $group['department'] ?? '',
                'graduation_year' => $group['graduation_year'] ?? '',
                'country' => $group['country'] ?? '',
                'about' => trim((string) ($site['about'] ?? $group['description'] ?? '')),
                'headline' => $site['headline'] ?? $group['name'] ?? '',
                'tagline' => $site['tagline'] ?? '',
                'members' => !empty($site['show_stats']) ? Membership::countActive($groupId) : null,
                'join_policy' => ($group['join_policy'] ?? 'approval') === 'invite' ? 'invitation only' : 'proof-backed approval',
                'visibility' => $group['visibility'] ?? 'public',
                'join_cta' => !empty($site['show_join']),
            ],
            'contact' => [
                'email' => $site['contact_email'] ?? '',
                'phone' => $site['contact_phone'] ?? '',
                'address' => $site['contact_address'] ?? '',
                'socials' => array_values(array_filter([
                    $site['facebook_url'] ?? null,
                    $site['instagram_url'] ?? null,
                    $site['x_url'] ?? null,
                    $site['linkedin_url'] ?? null,
                    $site['youtube_url'] ?? null,
                    $site['whatsapp_url'] ?? null,
                ])),
            ],
            'latest_news' => array_map(static fn (array $a): array => [
                'title' => $a['title'],
                'date' => date('M j, Y', strtotime((string) $a['created_at'])),
                'excerpt' => str_limit(strip_tags((string) $a['body']), 300),
            ], $announcements),
            'upcoming_events' => array_map(static fn (array $e): array => [
                'title' => $e['title'],
                'starts' => date('D, M j, Y · g:i A', strtotime((string) $e['starts_at'])),
                'location' => $e['location'] ?: 'Venue to be confirmed',
                'description' => str_limit(strip_tags((string) ($e['description'] ?? '')), 250),
            ], $events),
            'leadership' => array_map(static fn (array $m): array => [
                'name' => $m['name'],
                'role' => $m['position_title'] ?: role_label((string) $m['role']),
            ], $exco),
            'links' => [
                'website' => group_site_url((string) $site['subdomain']),
                'join' => main_url('join/' . $group['slug']),
                'sign_in' => main_url('login'),
                'explore' => main_url('explore'),
                'platform' => main_url(),
            ],
        ];
    }

    /**
     * Context for a signed-in member. Never includes other members' details.
     *
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    public static function platformContext(array $user): array
    {
        $userId = (int) $user['id'];
        $groups = Group::forUser($userId);
        $events = Event::upcomingForUser($userId, 5);

        return [
            'kind' => 'platform_help',
            'user' => [
                'first_name' => explode(' ', (string) ($user['name'] ?? 'there'))[0],
                'is_platform_admin' => is_admin(),
            ],
            'features' => [
                'groups' => 'Groups have announcements, events, members, dues, welfare, elections, treasury and an optional cooperative.',
                'joining' => 'Join requests include proof and stay pending until a group admin approves them. Some groups are invitation only.',
                'education' => 'Members can add institution, faculty, department and graduation records. Confirmed records drive matched-group and classmate suggestions.',
                'dues' => 'Group admins create dues cycles; members pay by bank transfer, Paystack or Stripe and admins confirm offline payments.',
                'welfare' => 'Welfare cases track contributions and funding progress; the ledger is visible to the group.',
                'elections' => 'Elections use a secret ballot, one vote per member per position, with live turnout and published results.',
                'coop' => 'A group cooperative supports savings, shares, loans with guarantors, fines, meetings and dividends.',
                'classmates' => 'Opt in from Profile → Classmates. Only confirmed education records and mutual opt-ins are matched.',
                'whatsapp' => 'Members opt in from Profile → WhatsApp updates. Group channels are invite links only; Classof does not read group chats.',
                'websites' => 'Each group has a public one-page website. Owners/admins edit it from Group → Website, or assign a website editor by email.',
            ],
            'my_groups' => array_map(static fn (array $g): array => [
                'name' => $g['name'],
                'scope' => $g['scope'],
                'role' => $g['role'],
                'status' => $g['membership_status'],
                'url' => main_url('groups/' . $g['slug']),
            ], $groups),
            'upcoming_events' => array_map(static fn (array $e): array => [
                'title' => $e['title'],
                'group' => $e['group_name'],
                'starts' => date('D, M j, Y · g:i A', strtotime((string) $e['starts_at'])),
                'location' => $e['location'] ?: 'Venue to be confirmed',
                'url' => main_url('groups/' . $e['group_slug'] . '/events/' . $e['id']),
            ], $events),
            'links' => [
                'dashboard' => main_url('dashboard'),
                'explore' => main_url('explore'),
                'profile' => main_url('profile'),
                'education' => main_url('profile/education'),
                'group_requests' => main_url('profile/group-requests'),
                'classmates' => main_url('classmates'),
                'admin' => main_url('admin'),
            ],
        ];
    }

    /**
     * @return array{answer: string, source: string, history: array<int, array{role: string, content: string}>, suggestions: array<int, string>}
     */
    public static function ask(string $scope, string $kind, string $message, array $context): array
    {
        $message = trim($message);
        $history = self::history($scope);

        if (self::rateLimited($scope)) {
            return [
                'answer' => 'You are sending messages too quickly. Please wait a few minutes and try again.',
                'source' => 'rate_limit',
                'history' => $history,
                'suggestions' => self::suggestions($kind),
            ];
        }

        $answer = null;
        $source = self::aiConfigured() ? 'local_fallback' : 'local';

        if (self::aiConfigured()) {
            try {
                $answer = self::requestAi($kind, $context, $history, $message);
                $source = 'ai';
            } catch (\Throwable) {
                $answer = null;
            }
        }

        if ($answer === null || trim($answer) === '') {
            $answer = self::localAnswer($kind, $message, $context);
        }

        $answer = mb_substr(trim($answer), 0, 4000);

        $history[] = ['role' => 'user', 'content' => $message];
        $history[] = ['role' => 'assistant', 'content' => $answer];
        $history = array_slice($history, -self::HISTORY_LIMIT);
        Session::set(self::historyKey($scope), $history);

        return [
            'answer' => $answer,
            'source' => $source,
            'history' => $history,
            'suggestions' => self::suggestions($kind),
        ];
    }

    public static function greeting(string $kind, array $context): string
    {
        if ($kind === 'group') {
            $name = (string) ($context['group']['name'] ?? 'this group');

            return 'Hi! Ask me about ' . $name . ' — joining, events, news, leadership or contact details.';
        }

        $name = (string) ($context['user']['first_name'] ?? 'there');

        return 'Hi ' . $name . '! I can help with groups, dues, welfare, elections, co-ops, classmates and your account.';
    }

    /** @return array<int, string> */
    public static function suggestions(string $kind): array
    {
        return $kind === 'group' ? self::groupSuggestions() : self::platformSuggestions();
    }

    /** @return array<int, array{role: string, content: string}> */
    public static function history(string $scope): array
    {
        $raw = Session::get(self::historyKey($scope), []);

        if (!is_array($raw)) {
            return [];
        }

        $history = [];

        foreach ($raw as $turn) {
            if (!is_array($turn)) {
                continue;
            }

            $role = (string) ($turn['role'] ?? '');
            $content = trim((string) ($turn['content'] ?? ''));

            if (in_array($role, ['user', 'assistant'], true) && $content !== '') {
                $history[] = ['role' => $role, 'content' => $content];
            }
        }

        return array_slice($history, -self::HISTORY_LIMIT);
    }

    public static function clear(string $scope): void
    {
        Session::forget(self::historyKey($scope));
    }

    private static function historyKey(string $scope): string
    {
        return 'assistant_history_' . md5($scope);
    }

    private static function rateLimited(string $scope): bool
    {
        $key = 'assistant_rate_' . md5($scope);
        $now = time();
        $hits = array_values(array_filter(
            (array) Session::get($key, []),
            static fn (mixed $time): bool => is_int($time) && $time > $now - self::RATE_WINDOW
        ));

        if (count($hits) >= self::RATE_LIMIT) {
            Session::set($key, $hits);

            return true;
        }

        $hits[] = $now;
        Session::set($key, $hits);

        return false;
    }

    /**
     * @param array<string, mixed> $context
     * @param array<int, array{role: string, content: string}> $history
     */
    private static function requestAi(string $kind, array $context, array $history, string $message): string
    {
        $role = $kind === 'group'
            ? 'You are the assistant on the public website of an alumni group. Answer only from the supplied context. Do not reveal private member data.'
            : 'You are the in-app help assistant for a signed-in Classof member. Answer only from the supplied context and the member\'s own group data.';

        $system = $role . "\n"
            . "Answer in at most 120 words in plain text. Be warm, direct and specific. "
            . "Use full URLs from the context when they help. Do not invent dates, people, payments, policies or achievements. "
            . "If the answer is not in the context, say you are not sure and point to the closest relevant page. "
            . "Never provide legal or financial advice.\n\n"
            . 'Context (trusted JSON): ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $messages = [['role' => 'system', 'content' => $system]];

        foreach (array_slice($history, -8) as $turn) {
            $messages[] = ['role' => $turn['role'], 'content' => $turn['content']];
        }

        $messages[] = ['role' => 'user', 'content' => $message];

        $payload = [
            'model' => (string) config('ai.model', 'gpt-4o-mini'),
            'temperature' => 0.35,
            'max_tokens' => 500,
            'messages' => $messages,
        ];

        $base = rtrim((string) config('ai.base_url', 'https://api.openai.com/v1'), '/');
        $handle = curl_init($base . '/chat/completions');
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . (string) config('ai.api_key'),
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => max(5, (int) config('ai.timeout', 20)),
        ]);

        $response = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $error = curl_error($handle);
        curl_close($handle);

        if ($response === false) {
            throw new RuntimeException('Assistant endpoint unreachable: ' . $error);
        }

        if ($status >= 400) {
            throw new RuntimeException('Assistant endpoint returned ' . $status . '.');
        }

        $decoded = json_decode((string) $response, true);
        $answer = $decoded['choices'][0]['message']['content'] ?? null;

        if (!is_string($answer) || trim($answer) === '') {
            throw new RuntimeException('Assistant endpoint returned no answer.');
        }

        return trim($answer);
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function localAnswer(string $kind, string $message, array $context): string
    {
        $text = mb_strtolower($message);
        $text = (string) preg_replace('/\s+/', ' ', $text);

        return $kind === 'group'
            ? self::localGroupAnswer($text, $context)
            : self::localPlatformAnswer($text, $context);
    }

    /** @param array<string, mixed> $context */
    private static function localGroupAnswer(string $text, array $context): string
    {
        $group = $context['group'];
        $links = $context['links'];
        $name = (string) $group['name'];

        if (self::has($text, ['join', 'apply', 'admission', 'sign up', 'signup', 'become a member'])) {
            if (($group['join_policy'] ?? '') === 'invitation only') {
                return $name . ' is invitation only. Ask the group leadership for an invite link. Contact details are on this page if they have been added.';
            }

            if (empty($group['join_cta'])) {
                return 'This page does not show a public join button. Sign in at ' . $links['sign_in']
                    . ' and open Explore to find ' . $name . ' and its current joining options.';
            }

            return 'Membership is proof-backed. Open ' . $links['join']
                . ' to submit your evidence. A group admin reviews it before you are admitted. '
                . 'You can sign in or create an account first at ' . $links['sign_in'] . '.';
        }

        if (self::has($text, ['event', 'meeting', 'reunion', 'calendar', 'upcoming', 'when is', 'when are'])) {
            $events = $context['upcoming_events'];

            if ($events === []) {
                return 'No upcoming events are listed on this page yet. Sign in at ' . $links['sign_in'] . ' to see the group calendar.';
            }

            $lines = array_map(
                static fn (array $e): string => '• ' . $e['title'] . ' — ' . $e['starts'] . ' at ' . $e['location'],
                $events
            );

            return "Upcoming events:\n" . implode("\n", $lines) . "\n\nSign in to RSVP: " . $links['sign_in'];
        }

        if (self::has($text, ['leader', 'leadership', 'executive', 'exco', 'president', 'chair', 'officer', 'committee'])) {
            if ($context['leadership'] === []) {
                return 'Leadership details are not listed publicly yet. Sign in at ' . $links['sign_in'] . ' to see the group team.';
            }

            $lines = array_map(static fn (array $p): string => '• ' . $p['name'] . ' — ' . $p['role'], $context['leadership']);

            return "Group leadership:\n" . implode("\n", $lines);
        }

        if (self::has($text, ['news', 'announcement', 'update', 'latest', 'happening'])) {
            if ($context['latest_news'] === []) {
                return 'There are no public announcements on this page yet. Sign in at ' . $links['sign_in'] . ' for the group feed.';
            }

            $lines = array_map(
                static fn (array $a): string => '• ' . $a['title'] . ' (' . $a['date'] . ')',
                $context['latest_news']
            );

            return "Latest news:\n" . implode("\n", $lines) . "\n\nSign in to read the full posts: " . $links['sign_in'];
        }

        if (self::has($text, ['contact', 'email', 'phone', 'whatsapp', 'address', 'reach', 'speak to'])) {
            $contact = $context['contact'];
            $parts = [];

            if ($contact['email'] !== '') {
                $parts[] = 'Email: ' . $contact['email'];
            }
            if ($contact['phone'] !== '') {
                $parts[] = 'Phone: ' . $contact['phone'];
            }
            if ($contact['address'] !== '') {
                $parts[] = 'Address: ' . $contact['address'];
            }
            if ($contact['socials'] !== []) {
                $parts[] = "Social links:\n" . implode("\n", array_map(static fn (string $url): string => '• ' . $url, $contact['socials']));
            }

            return $parts === []
                ? 'Public contact details have not been added yet. Use the join request at ' . $links['join'] . ' or sign in at ' . $links['sign_in'] . '.'
                : implode("\n", $parts);
        }

        if (self::has($text, ['how many', 'member count', 'members'])) {
            if ($group['members'] === null) {
                return 'The group does not publish its member count on this page. Sign in at ' . $links['sign_in'] . ' for the member directory.';
            }

            return $name . ' has ' . (int) $group['members'] . ' active member' . ((int) $group['members'] === 1 ? '' : 's') . '. Membership is ' . $group['join_policy'] . '.';
        }

        if (self::has($text, ['dues', 'welfare', 'election', 'vote', 'coop', 'co-op', 'cooperative', 'loan', 'savings', 'shares'])) {
            return 'Dues, welfare, elections and the cooperative are available to signed-in members. Open the group in Classof at '
                . $links['sign_in'] . ' to use those areas.';
        }

        if (self::has($text, ['classof', 'platform', 'what is this site', 'who runs this site'])) {
            return $name . ' uses Classof for membership, news, events, welfare, elections and group finances. '
                . 'This public page is at ' . $links['website'] . ' and the main platform is at ' . $links['platform'] . '.';
        }

        if (self::has($text, ['website', 'url', 'link'])) {
            return 'This group website is at ' . $links['website'] . '. Membership and group tools are in Classof at ' . $links['sign_in'] . '.';
        }

        if (self::has($text, ['hello', 'hi ', 'hey', 'good morning', 'good afternoon', 'good evening'])) {
            return 'Hello! Ask me about joining ' . $name . ', upcoming events, news, leadership or contact details.';
        }

        if (self::has($text, ['thank', 'thanks'])) {
            return 'You are welcome. If you need anything else, ask about membership, events, news or leadership.';
        }

        $about = trim((string) $group['about']);

        if (self::has($text, ['about', 'who are', 'what is', 'history', 'tell me'])) {
            return $about !== '' ? str_limit($about, 700) : $name . ' is ' . $group['affiliation'] . ' at ' . $group['institution'] . '.';
        }

        return $name . ' is ' . $group['affiliation'] . ' at ' . $group['institution'] . '. '
            . 'I can help with joining, upcoming events, latest news, leadership and contact details.';
    }

    /** @param array<string, mixed> $context */
    private static function localPlatformAnswer(string $text, array $context): string
    {
        $links = $context['links'];

        if (self::has($text, ['join a group', 'join group', 'request to join', 'become a member', 'proof', 'approval'])) {
            return 'Open Explore at ' . $links['explore'] . ' and choose a group. Submit proof of affiliation; a group admin reviews and approves the request. '
                . 'If your education record matches a group, the dashboard also suggests it. Class, faculty, department and association groups keep proof review by default.';
        }

        if (self::has($text, ['create a group', 'request a group', 'new group', 'start a group'])) {
            return 'Group creation is request-and-verify. Use ' . $links['group_requests']
                . ' to request a new group. A platform admin checks for duplicates and verifies the institution before the group is published; you become the owner.';
        }

        if (self::has($text, ['dues', 'pay', 'payment', 'receipt', 'outstanding', 'defaulters'])) {
            return 'Dues are created by group admins. Open your group and choose Dues to see what you owe and start a payment. '
                . 'Bank transfer, Paystack and Stripe are supported; offline transfers are confirmed by an admin. Receipts appear in your payment history. '
                . 'Start at ' . $links['dashboard'] . '.';
        }

        if (self::has($text, ['welfare', 'bereavement', 'medical', 'fund'])) {
            return 'Welfare cases record contributions and funding progress. Open a group from ' . $links['dashboard']
                . ' and choose Welfare to see open cases and contribute. Contributions are recorded against your name and appear in the group ledger.';
        }

        if (self::has($text, ['election', 'vote', 'ballot', 'candidate', 'position'])) {
            return 'Elections run inside each group. Live elections appear on your dashboard and in the group’s Elections tab. '
                . 'Voting is one secret ballot per member per position while the election is open. Results appear after an admin publishes them.';
        }

        if (self::has($text, ['coop', 'co-op', 'cooperative', 'loan', 'savings', 'shares', 'guarantor', 'dividend', 'fine'])) {
            return 'A group’s cooperative is optional and separately joined. It supports savings, share purchases, loans with guarantors, fines, meetings and dividends. '
                . 'Open a group from ' . $links['dashboard'] . ' and choose Co-op. Loan applications need the required accepted guarantors before approval.';
        }

        if (self::has($text, ['classmate', 'classmates', 'find classmates'])) {
            return 'Classmate discovery is opt-in. Go to ' . $links['classmates']
                . '. Only members with the same confirmed institution and graduation year are shown, and both sides must opt in. No contacts are uploaded.';
        }

        if (self::has($text, ['whatsapp', 'notification', 'text message', 'opt in'])) {
            return 'WhatsApp updates are optional. Open your profile at ' . $links['profile']
                . ' and add an international number, then opt in. Group WhatsApp channels are invite links only; Classof cannot read or post inside a WhatsApp group.';
        }

        if (self::has($text, ['website', 'landing page', 'subdomain', 'site editor'])) {
            return 'Every group has a public one-page website. Owners and admins edit it from Group → Website. '
                . 'They can also assign a registered member or contracted editor by email. The site can be draft or published and includes news, events, leadership and contact sections.';
        }

        if (self::has($text, ['education', 'institution', 'faculty', 'department', 'graduation', 'programme'])) {
            return 'Add or correct your education at ' . $links['education']
                . '. Institution, faculty, department and graduation year drive matched-group suggestions. Platform confirmation is separate from group membership approval.';
        }

        if (self::has($text, ['my group', 'which group', 'groups i', 'memberships'])) {
            $groups = $context['my_groups'];

            if ($groups === []) {
                return 'You are not in a group yet. Explore groups at ' . $links['explore'] . ' or request a new one at ' . $links['group_requests'] . '.';
            }

            $lines = array_map(
                static fn (array $g): string => '• ' . $g['name'] . ' — ' . ucfirst($g['role']) . ', ' . $g['status'] . ': ' . $g['url'],
                $groups
            );

            return "Your groups:\n" . implode("\n", $lines);
        }

        if (self::has($text, ['event', 'upcoming', 'calendar', 'meeting'])) {
            $events = $context['upcoming_events'];

            if ($events === []) {
                return 'You have no upcoming events in your groups. Check ' . $links['dashboard'] . ' or open a group calendar.';
            }

            $lines = array_map(
                static fn (array $e): string => '• ' . $e['title'] . ' — ' . $e['group'] . ', ' . $e['starts'] . ' at ' . $e['location'] . ': ' . $e['url'],
                $events
            );

            return "Your upcoming events:\n" . implode("\n", $lines);
        }

        if (self::has($text, ['admin', 'platform settings'])) {
            return !empty($context['user']['is_platform_admin'])
                ? 'Open the admin console at ' . $links['admin'] . ' for users, groups, institutions, education review, group requests, platform settings and the WhatsApp outbox.'
                : 'Platform administration is restricted to platform admins. Group owners and admins manage their own group from its Settings tab.';
        }

        if (self::has($text, ['password', 'account', 'profile', 'email address', 'login'])) {
            return 'Open your profile at ' . $links['profile'] . ' to update your details or change your password. WhatsApp preferences and education records are also linked from there.';
        }

        if (self::has($text, ['hello', 'hi ', 'hey', 'good morning', 'good afternoon', 'good evening'])) {
            return 'Hello ' . $context['user']['first_name'] . '! Ask me about joining groups, dues, welfare, elections, the co-op, classmates or your account.';
        }

        if (self::has($text, ['thank', 'thanks'])) {
            return 'You are welcome. Ask me any time about groups, payments, welfare, elections or the co-op.';
        }

        return 'I can help with joining a group, dues and payments, welfare, elections, the co-op, classmates, WhatsApp updates, group websites and your account. '
            . 'For a full view of your groups, open ' . $links['dashboard'] . '.';
    }

    /** @param array<int, string> $needles */
    private static function has(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }
}
