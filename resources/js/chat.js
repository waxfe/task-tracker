const messagesArea = document.getElementById('messagesArea');
const messageInput = document.getElementById('messageInput');
const sendBtn = document.getElementById('sendBtn');
const contextSelect = document.getElementById('contextSelect');

function scrollToBottom() {
    messagesArea.scrollTop = messagesArea.scrollHeight;
}

function addMessage(message, isUser, suggestions = []) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${isUser ? 'user' : 'ai'}`;

    const bubble = document.createElement('div');
    bubble.className = 'message-bubble';

    // Для AI — рендерим markdown, для юзера — экранируем HTML
    const renderedMessage = isUser
        ? `<p>${escapeHtml(message)}</p>`
        : marked.parse(message);

    bubble.innerHTML = `
        <div class="message-header">
            <span class="sender">${isUser ? 'Вы' : 'AI-ассистент'}</span>
            <span class="time">${new Date().toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })}</span>
        </div>
        <div class="message-text ${isUser ? '' : 'ai-response'}">${renderedMessage}</div>
        ${!isUser && suggestions.length ? `
            <div class="suggestions">
                ${suggestions.map(s => `<button class="suggestion-chip" onclick="askSuggestion('${escapeHtml(s).replace(/'/g, "\\'")}')">${escapeHtml(s)}</button>`).join('')}
            </div>
        ` : ''}
    `;

    messageDiv.appendChild(bubble);
    messagesArea.appendChild(messageDiv);
    scrollToBottom();
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function (m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

function sendMessage() {
    const message = messageInput.value.trim();
    if (!message) return;

    addMessage(message, true);
    messageInput.value = '';
    messageInput.style.height = 'auto';

    const contextValue = contextSelect.value;
    let contextType = 'general';
    let contextId = null;

    if (contextValue.startsWith('project_')) {
        contextType = 'project';
        contextId = contextValue.split('_')[1];
    }

    fetch('/ai-chat', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            message: message,
            context_type: contextType,
            context_id: contextId
        })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                addMessage(data.reply, false, data.suggestions || []);
            } else {
                addMessage('Извините, произошла ошибка. Попробуйте позже.', false);
            }
        })
        .catch(err => {
            console.error(err);
            addMessage('Ошибка соединения с сервером.', false);
        });
}

window.askSuggestion = function (suggestion) {
    messageInput.value = suggestion;
    sendMessage();
};

sendBtn.addEventListener('click', sendMessage);
messageInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

// Авто-высота textarea
messageInput.addEventListener('input', function () {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});

// Скролл вниз при загрузке
scrollToBottom();

// Очистка истории
const clearHistoryBtn = document.getElementById('clearHistoryBtn');

clearHistoryBtn?.addEventListener('click', () => {
    if (confirm('Вы уверены, что хотите очистить всю историю сообщений?')) {
        fetch('/ai-chat/history', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Очищаем область сообщений
                    messagesArea.innerHTML = '';
                    showMessage('История очищена', false);
                } else {
                    showMessage('Ошибка при очистке', true);
                }
            })
            .catch(err => {
                console.error(err);
                showMessage('Ошибка соединения', true);
            });
    }
});

function showMessage(msg, isError = false) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `toast-message ${isError ? 'error' : 'success'}`;
    messageDiv.textContent = msg;
    document.body.appendChild(messageDiv);
    setTimeout(() => messageDiv.remove(), 3000);
}

