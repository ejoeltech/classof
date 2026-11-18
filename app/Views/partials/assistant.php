<?php
$assistantKind = $assistantKind ?? 'platform';
$assistantTitle = $assistantTitle ?? 'Classof assistant';
$assistantSubtitle = $assistantSubtitle ?? ($assistantKind === 'group' ? 'Group guide' : 'Classof guide');
$assistantGreeting = $assistantGreeting ?? 'Hi! How can I help?';
$assistantSuggestions = $assistantSuggestions ?? [];
$assistantHistory = $assistantHistory ?? [];
$assistantAccent = $assistantAccent ?? '';
$assistantAccentStyle = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $assistantAccent) === 1
    ? '--assistant-accent: ' . $assistantAccent . ';'
    : '';
?>
<div class="assistant"
     data-assistant
     data-endpoint="<?= e($assistantEndpoint) ?>"
     data-token="<?= e(csrf_token()) ?>"
     data-greeting="<?= e($assistantGreeting) ?>"
     data-suggestions="<?= e(json_encode($assistantSuggestions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
     data-history="<?= e(json_encode($assistantHistory, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
     style="<?= e($assistantAccentStyle) ?>">
    <button type="button" class="assistant-launcher" data-assistant-toggle aria-expanded="false" aria-controls="assistant-panel">
        <?= icon('message', 19) ?>
        <span>Ask</span>
    </button>

    <section class="assistant-panel" id="assistant-panel" data-assistant-panel hidden aria-label="<?= e($assistantTitle) ?>">
        <header class="assistant-head">
            <div class="assistant-head-text">
                <strong><?= e($assistantTitle) ?></strong>
                <span><?= e($assistantSubtitle) ?></span>
            </div>
            <button type="button" class="assistant-icon-button" data-assistant-close aria-label="Close assistant"><?= icon('x', 18) ?></button>
        </header>

        <div class="assistant-messages" data-assistant-messages aria-live="polite"></div>

        <div class="assistant-suggestions" data-assistant-suggestions></div>

        <form class="assistant-form" data-assistant-form>
            <input type="text" name="message" maxlength="800" placeholder="Ask a question…" autocomplete="off" data-assistant-input aria-label="Your question">
            <button type="submit" class="assistant-send" data-assistant-send>Send</button>
        </form>

        <footer class="assistant-foot">
            <span>Answers can be wrong. Check important details.</span>
            <button type="button" data-assistant-reset>New chat</button>
        </footer>
    </section>
</div>
