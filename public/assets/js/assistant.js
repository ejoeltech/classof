(() => {
    const root = document.querySelector('[data-assistant]');

    if (!root) {
        return;
    }

    const panel = root.querySelector('[data-assistant-panel]');
    const launcher = root.querySelector('[data-assistant-toggle]');
    const closeButton = root.querySelector('[data-assistant-close]');
    const messages = root.querySelector('[data-assistant-messages]');
    const suggestionsBox = root.querySelector('[data-assistant-suggestions]');
    const form = root.querySelector('[data-assistant-form]');
    const input = root.querySelector('[data-assistant-input]');
    const sendButton = root.querySelector('[data-assistant-send]');
    const resetButton = root.querySelector('[data-assistant-reset]');

    if (!panel || !messages || !form || !input) {
        return;
    }

    const endpoint = root.dataset.endpoint || '';
    const token = root.dataset.token || '';
    const greeting = root.dataset.greeting || 'Hi! How can I help?';
    let initialSuggestions = [];
    let history = [];
    let busy = false;

    try {
        initialSuggestions = JSON.parse(root.dataset.suggestions || '[]');
    } catch (error) {
        initialSuggestions = [];
    }

    try {
        history = JSON.parse(root.dataset.history || '[]');
    } catch (error) {
        history = [];
    }

    const sourceLabel = (source) => {
        switch (source) {
            case 'ai':
                return 'AI';
            case 'local_fallback':
            case 'local':
                return 'Guide';
            case 'rate_limit':
                return 'Notice';
            default:
                return '';
        }
    };

    const linkify = (text) => {
        const fragment = document.createDocumentFragment();
        const parts = String(text).split(/(https?:\/\/[^\s]+)/g);

        parts.forEach((part) => {
            if (!/^https?:\/\//.test(part)) {
                fragment.appendChild(document.createTextNode(part));
                return;
            }

            let url = part;
            let trailing = '';

            while (/[.,;:!?)]$/.test(url)) {
                trailing = url.slice(-1) + trailing;
                url = url.slice(0, -1);
            }

            const link = document.createElement('a');
            link.href = url;
            link.textContent = url;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            fragment.appendChild(link);

            if (trailing) {
                fragment.appendChild(document.createTextNode(trailing));
            }
        });

        return fragment;
    };

    const scrollToBottom = () => {
        messages.scrollTop = messages.scrollHeight;
    };

    const addMessage = (role, text, source = '', error = false) => {
        const bubble = document.createElement('div');
        bubble.className = 'assistant-msg ' + (role === 'user' ? 'assistant-msg-user' : 'assistant-msg-assistant');

        if (error) {
            bubble.classList.add('assistant-msg-error');
        }

        const body = document.createElement('div');
        body.className = 'assistant-msg-text';
        body.appendChild(linkify(text));
        bubble.appendChild(body);

        const label = sourceLabel(source);

        if (role !== 'user' && label !== '') {
            const badge = document.createElement('span');
            badge.className = 'assistant-source';
            badge.textContent = label;
            bubble.appendChild(badge);
        }

        messages.appendChild(bubble);
        scrollToBottom();

        return bubble;
    };

    const addTyping = () => {
        const typing = document.createElement('div');
        typing.className = 'assistant-typing';
        typing.dataset.assistantTyping = '1';
        typing.textContent = 'Thinking…';
        messages.appendChild(typing);
        scrollToBottom();

        return typing;
    };

    const setSuggestions = (items) => {
        suggestionsBox.replaceChildren();

        (Array.isArray(items) ? items : []).slice(0, 4).forEach((text) => {
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'assistant-chip';
            chip.textContent = text;
            chip.addEventListener('click', () => send(text));
            suggestionsBox.appendChild(chip);
        });
    };

    const openPanel = () => {
        panel.hidden = false;
        launcher?.setAttribute('aria-expanded', 'true');
        window.setTimeout(() => input.focus(), 30);
        scrollToBottom();
    };

    const closePanel = () => {
        panel.hidden = true;
        launcher?.setAttribute('aria-expanded', 'false');
    };

    const showGreeting = (text = greeting) => {
        addMessage('assistant', text);
    };

    const request = async (payload) => {
        const response = await fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
            },
            body: new URLSearchParams(payload),
        });

        let data = {};

        try {
            data = await response.json();
        } catch (error) {
            data = {};
        }

        if (!response.ok) {
            throw new Error(data.error || 'The assistant is unavailable right now.');
        }

        return data;
    };

    const send = async (text) => {
        const message = String(text || input.value || '').trim();

        if (busy || message === '' || endpoint === '') {
            return;
        }

        busy = true;
        openPanel();
        addMessage('user', message);
        setSuggestions([]);
        input.value = '';
        sendButton.disabled = true;
        const typing = addTyping();

        try {
            const data = await request({ _token: token, message });
            typing.remove();

            if (data.answer) {
                addMessage('assistant', data.answer, data.source || '');
            }

            setSuggestions(data.suggestions || []);
        } catch (error) {
            typing.remove();
            addMessage('assistant', error.message || 'Sorry, the assistant could not answer that. Please try a shorter question.', 'error', true);
        } finally {
            busy = false;
            sendButton.disabled = false;
            input.focus();
            scrollToBottom();
        }
    };

    const reset = async () => {
        if (busy || endpoint === '') {
            return;
        }

        busy = true;
        sendButton.disabled = true;
        messages.replaceChildren();

        try {
            const data = await request({ _token: token, reset: '1' });
            showGreeting(data.greeting || greeting);
            setSuggestions(data.suggestions || initialSuggestions);
        } catch (error) {
            showGreeting(error.message || greeting);
            setSuggestions(initialSuggestions);
        } finally {
            busy = false;
            sendButton.disabled = false;
            input.focus();
        }
    };

    launcher?.addEventListener('click', () => {
        if (panel.hidden) {
            openPanel();
        } else {
            closePanel();
        }
    });

    closeButton?.addEventListener('click', closePanel);
    resetButton?.addEventListener('click', reset);

    document.querySelectorAll('[data-assistant-open]').forEach((trigger) => {
        trigger.addEventListener('click', openPanel);
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        send(input.value);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !panel.hidden) {
            closePanel();
        }
    });

    if (history.length > 0) {
        history.slice(-10).forEach((turn) => {
            if (turn && turn.content && (turn.role === 'user' || turn.role === 'assistant')) {
                addMessage(turn.role, turn.content);
            }
        });
        setSuggestions(initialSuggestions);
    } else {
        showGreeting();
        setSuggestions(initialSuggestions);
    }

    if (window.location.hash === '#assistant') {
        openPanel();
    }
})();
