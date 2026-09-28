// floating AI chat widget - page pe teeno mein se ek hi hota hai (panel.blade.php), jo hai usay wire karta hai
document.querySelectorAll('[data-agent-widget]').forEach((widget) => {
    const chatUrl = widget.dataset.chatUrl;
    const historyUrl = widget.dataset.historyUrl;
    const markReadUrl = widget.dataset.markReadUrl;
    const messageList = widget.querySelector('[data-agent-message-list]');
    const emptyHint = widget.querySelector('[data-agent-empty-hint]');
    const form = widget.querySelector('[data-agent-form]');
    const input = widget.querySelector('[data-agent-input]');
    const micButton = widget.querySelector('[data-agent-mic-button]');
    const voiceToggle = widget.querySelector('[data-agent-voice-toggle]');
    const replyQuote = widget.querySelector('[data-agent-reply-quote]');
    const replyQuoteText = widget.querySelector('[data-agent-reply-quote-text]');
    const replyQuoteClear = widget.querySelector('[data-agent-reply-quote-clear]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    // default off - koi on kare tab hi assistant bolta hai
    let voiceOutputOn = false;

    // jis message ka reply diya ja raha hai - null matlab normal naya message
    let replyingTo = null;

    function setReplyTarget(messageId, quotedText) {
        replyingTo = messageId;
        replyQuoteText.textContent = quotedText;
        replyQuote.hidden = false;
        input.focus();
    }

    function clearReplyTarget() {
        replyingTo = null;
        replyQuote.hidden = true;
    }

    replyQuoteClear?.addEventListener('click', clearReplyTarget);

    // proactive message ke neeche "MarketLink AI - update" caption
    function appendProactiveCaption(row) {
        const caption = document.createElement('p');
        caption.className = 'mb-1 flex items-center gap-1 text-[11px] text-soil-muted';

        const icon = document.createElement('iconify-icon');
        icon.setAttribute('icon', 'tabler:sparkles');
        caption.appendChild(icon);
        caption.appendChild(document.createTextNode('MarketLink AI update'));

        row.appendChild(caption);
    }

    // reply icon - is bubble ka reply state set kar deta hai
    function appendReplyButton(row, messageId, quotedText) {
        const replyButton = document.createElement('button');
        replyButton.type = 'button';
        replyButton.className = 'mt-1 flex items-center gap-1 text-[11px] text-soil-muted hover:text-soil';
        replyButton.setAttribute('aria-label', 'Reply to this message');

        const icon = document.createElement('iconify-icon');
        icon.setAttribute('icon', 'tabler:corner-up-left');
        replyButton.appendChild(icon);
        replyButton.appendChild(document.createTextNode('Reply'));

        replyButton.addEventListener('click', () => setReplyTarget(messageId, quotedText));
        row.appendChild(replyButton);
    }

    // quick-reply buttons (Accept / Decline / Details...) - dabate hi wahi label reply ke saath bhej dete hain
    function appendActionButtons(row, messageId, actions) {
        const actionRow = document.createElement('div');
        actionRow.dataset.agentActions = 'true';
        actionRow.className = 'mt-2 flex flex-wrap gap-1.5';

        actions.forEach((actionLabel) => {
            const actionButton = document.createElement('button');
            actionButton.type = 'button';
            actionButton.className = 'btn-outline !px-3 !py-1 text-xs';
            actionButton.textContent = actionLabel;
            actionButton.addEventListener('click', () => sendMessage(actionLabel, messageId));
            actionRow.appendChild(actionButton);
        });

        row.appendChild(actionRow);
    }

    // kaam ho jaane ke baad buttons ki jagah chhota muted label - "✓ Accepted" / "No longer available"
    function appendResolvedLabel(row, resolvedText) {
        const label = document.createElement('p');
        label.dataset.agentResolved = 'true';
        label.className = 'mt-2 text-xs text-soil-muted';
        label.textContent = resolvedText;
        row.appendChild(label);
    }

    function appendBubble({ id = null, role, content, kind = 'normal', actions = null, resolved = null }) {
        emptyHint?.remove();

        const row = document.createElement('div');
        row.className = role === 'user' ? 'flex justify-end' : 'flex flex-col items-start';

        const bubbleWrap = document.createElement('div');
        bubbleWrap.className = 'max-w-[85%]';
        if (id) bubbleWrap.dataset.messageId = id;

        if (kind === 'proactive') {
            appendProactiveCaption(bubbleWrap);
        }

        const bubble = document.createElement('div');
        bubble.className = [
            'whitespace-pre-line rounded-2xl px-3.5 py-2 text-sm',
            role === 'user' ? 'bg-leaf-500 text-white' : kind === 'proactive'
                ? 'border border-leaf-100 bg-leaf-50 text-soil'
                : 'bg-cream text-soil',
        ].join(' ');
        bubble.textContent = content;
        bubbleWrap.appendChild(bubble);

        if (role === 'assistant' && id) {
            appendReplyButton(bubbleWrap, id, content);
        }

        if (kind === 'proactive' && Array.isArray(actions) && actions.length) {
            if (resolved) {
                appendResolvedLabel(bubbleWrap, resolved);
            } else {
                appendActionButtons(bubbleWrap, id, actions);
            }
        }

        row.appendChild(bubbleWrap);
        messageList.appendChild(row);
        messageList.scrollTop = messageList.scrollHeight;

        return bubble;
    }

    // farmer/admin ne kaam kar diya ho to already-rendered proactive messages ke buttons label mein badal do
    async function refreshProactiveStatuses() {
        try {
            const response = await fetch(historyUrl, { headers: { Accept: 'application/json' } });
            const data = await response.json();

            (data.messages ?? []).forEach((message) => {
                if (! message.resolved) return;

                const bubbleWrap = messageList.querySelector(`[data-message-id="${message.id}"]`);
                if (! bubbleWrap || bubbleWrap.querySelector('[data-agent-resolved]')) return;

                bubbleWrap.querySelector('[data-agent-actions]')?.remove();
                appendResolvedLabel(bubbleWrap, message.resolved);
            });
        } catch (error) {
            // agli baar chat khulne pe history se theek ho jayega
        }
    }

    function speak(text) {
        if (! voiceOutputOn || typeof speechSynthesis === 'undefined') return;

        // speech engines emoji ko ajeeb awaaz bana dete hain, pehle hata do
        const spokenText = text.replace(/[\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}]/gu, '');
        speechSynthesis.cancel();
        speechSynthesis.speak(new SpeechSynthesisUtterance(spokenText));
    }

    async function loadHistory() {
        try {
            const response = await fetch(historyUrl, { headers: { Accept: 'application/json' } });
            const data = await response.json();
            (data.messages ?? []).forEach((message) => appendBubble(message));
        } catch (error) {
            // history na aaye to khali hi reh jaye, user ko pareshan karne ki zaroorat nahi
        }
    }

    async function markRead() {
        try {
            await fetch(markReadUrl, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
            });
        } catch (error) {
            // badge dobara poll pe theek ho jayega
        }
    }

    async function sendMessage(messageText, replyToMessageId = null) {
        const replyingToNow = replyToMessageId ?? replyingTo;

        appendBubble({ role: 'user', content: messageText });
        clearReplyTarget();
        const pendingBubble = appendBubble({ role: 'assistant', content: 'Typing...' });

        try {
            const response = await fetch(chatUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ message: messageText, reply_to_message_id: replyingToNow }),
            });

            const data = await response.json();

            // 429 = 20 messages per minute limit (AppServiceProvider) - server ka message dikhao, bol ke mat sunao
            if (response.status === 429) {
                pendingBubble.textContent = data.reply;
                return;
            }

            const reply = data.reply || "Sorry, I didn't catch that.";
            pendingBubble.textContent = reply;
            // Typing... bubble ke reply button ko asal message id chahiye, pehle nahi mil sakta tha
            if (data.message_id) {
                appendReplyButton(pendingBubble.parentElement, data.message_id, reply);
            }
            speak(reply);
            // AI ne isi turn mein order/farmer/post/product resolve kar diya ho to buttons label mein badal jayen
            refreshProactiveStatuses();
        } catch (error) {
            pendingBubble.textContent = "Sorry, I'm having a technical problem right now. Please try again in a moment.";
        } finally {
            messageList.scrollTop = messageList.scrollHeight;
        }
    }

    form?.addEventListener('submit', (event) => {
        event.preventDefault();
        const messageText = input.value.trim();
        if (! messageText) return;

        input.value = '';
        sendMessage(messageText);
    });

    // page ke doosre buttons (jaise "Need it within the hour?") chat ko message likh ke kholte hain - send customer khud dabata hai
    window.addEventListener('open-agent-chat', (event) => {
        input.value = event.detail?.message ?? '';
        setTimeout(() => input.focus(), 50);
    });

    // Alpine widget khulte hi (button ya open-agent-chat event se) proactive messages read mark ho jate hain
    widget.addEventListener('agent-chat-opened', markRead);

    voiceToggle?.addEventListener('click', () => {
        voiceOutputOn = ! voiceOutputOn;
        voiceToggle.querySelector('iconify-icon')?.setAttribute('icon', voiceOutputOn ? 'tabler:volume' : 'tabler:volume-off');
        if (! voiceOutputOn && typeof speechSynthesis !== 'undefined') speechSynthesis.cancel();
    });

    // har tap pe ek poora jumla, hamesha sunta nahi rehta - continuous mode background shor pakar leta aur beech mein kaat deta
    const SpeechRecognitionClass = window.SpeechRecognition || window.webkitSpeechRecognition;

    if (SpeechRecognitionClass && micButton) {
        micButton.addEventListener('click', () => {
            // har tap pe naya instance - purana reuse karne se pehli baar ke baad kaam band ho jata hai
            const recognizer = new SpeechRecognitionClass();
            recognizer.continuous = false;
            recognizer.interimResults = false;

            micButton.classList.add('bg-tomato-500', 'text-white');

            recognizer.addEventListener('result', (event) => {
                const transcript = event.results[0][0].transcript;
                input.value = transcript;
                sendMessage(transcript);
            });

            recognizer.addEventListener('end', () => micButton.classList.remove('bg-tomato-500', 'text-white'));
            recognizer.addEventListener('error', () => micButton.classList.remove('bg-tomato-500', 'text-white'));

            recognizer.start();
        });
    } else if (micButton) {
        micButton.disabled = true;
        micButton.title = "Your browser can't do voice input";
    }

    loadHistory();
});
