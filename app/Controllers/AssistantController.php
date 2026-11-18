<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\PlatformSetting;
use App\Services\AssistantService;

final class AssistantController extends Controller
{
    public function chat(): void
    {
        if (!PlatformSetting::bool('assistant.enabled')) {
            Response::json(['error' => 'The assistant is switched off.'], 403);
        }

        $user = $this->currentUser();
        $scope = 'app:' . (int) $user['id'];
        $context = AssistantService::platformContext($user);

        if (Request::boolean('reset')) {
            AssistantService::clear($scope);
            Response::json([
                'answer' => '',
                'source' => 'reset',
                'history' => [],
                'suggestions' => AssistantService::platformSuggestions(),
                'greeting' => AssistantService::greeting('platform', $context),
            ]);
        }

        $message = trim((string) Request::input('message', ''));

        if ($message === '') {
            Response::json(['error' => 'Type a question first.'], 422);
        }

        if (mb_strlen($message) > 800) {
            Response::json(['error' => 'Please keep questions under 800 characters.'], 422);
        }

        $result = AssistantService::ask($scope, 'platform', $message, $context);
        $result['greeting'] = AssistantService::greeting('platform', $context);

        Response::json($result);
    }
}
