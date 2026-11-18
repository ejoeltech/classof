<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Group;
use App\Models\GroupSite;
use App\Models\PlatformSetting;
use App\Services\AssistantService;

final class SiteChatController extends Controller
{
    public function chat(string $subdomain): void
    {
        if (!PlatformSetting::bool('assistant.enabled')) {
            Response::json(['error' => 'The assistant is switched off.'], 403);
        }

        $site = GroupSite::findBySubdomain(GroupSite::normalizeSubdomain($subdomain));

        if ($site === null || $site['status'] !== 'published') {
            Response::json(['error' => 'That group website is not available.'], 404);
        }

        $group = Group::find((int) $site['group_id']);

        if ($group === null || ($group['status'] ?? 'active') !== 'active') {
            Response::json(['error' => 'That group website is not available.'], 404);
        }

        $scope = 'site:' . (string) $site['subdomain'];
        $context = AssistantService::groupSiteContext($group, $site);

        if (Request::boolean('reset')) {
            AssistantService::clear($scope);
            Response::json([
                'answer' => '',
                'source' => 'reset',
                'history' => [],
                'suggestions' => AssistantService::groupSuggestions(),
                'greeting' => AssistantService::greeting('group', $context),
            ]);
        }

        $message = trim((string) Request::input('message', ''));

        if ($message === '') {
            Response::json(['error' => 'Type a question first.'], 422);
        }

        if (mb_strlen($message) > 800) {
            Response::json(['error' => 'Please keep questions under 800 characters.'], 422);
        }

        $result = AssistantService::ask($scope, 'group', $message, $context);
        $result['greeting'] = AssistantService::greeting('group', $context);

        Response::json($result);
    }
}
