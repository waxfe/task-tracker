const messagesArea = document.getElementById('messagesArea');
const messageInput = document.getElementById('messageInput');
const sendBtn = document.getElementById('sendBtn');
const contextSelect = document.getElementById('contextSelect');

function scrollToBottom() {
    messagesArea.scrollTop = messagesArea.scrollHeight;
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

function addMessage(message, isUser, suggestions = []) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${isUser ? 'user' : 'ai'}`;

    const bubble = document.createElement('div');
    bubble.className = 'message-bubble';

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

messageInput.addEventListener('input', function () {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});

scrollToBottom();

// ========== ОЧИСТКА ИСТОРИИ С МОДАЛКОЙ ==========
function openClearHistoryModal() {
    document.getElementById('clearHistoryModal').classList.remove('hidden');
}

function closeClearHistoryModal() {
    document.getElementById('clearHistoryModal').classList.add('hidden');
}

function executeClearHistory() {
    fetch('/ai-chat/history', {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                messagesArea.innerHTML = '';
                showMessage('История очищена', false);
                closeClearHistoryModal();
            } else {
                showMessage('Ошибка при очистке', true);
                closeClearHistoryModal();
            }
        })
        .catch(err => {
            console.error(err);
            showMessage('Ошибка соединения', true);
            closeClearHistoryModal();
        });
}

// Делаем функции глобальными для onclick
window.openClearHistoryModal = openClearHistoryModal;
window.closeClearHistoryModal = closeClearHistoryModal;

// Кнопка очистки
const clearHistoryBtn = document.getElementById('clearHistoryBtn');
if (clearHistoryBtn) {
    clearHistoryBtn.addEventListener('click', openClearHistoryModal);
}

// Кнопка подтверждения
const confirmClearHistoryBtn = document.getElementById('confirmClearHistoryBtn');
if (confirmClearHistoryBtn) {
    confirmClearHistoryBtn.addEventListener('click', executeClearHistory);
}

function showMessage(msg, isError = false) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `toast-message ${isError ? 'error' : 'success'}`;
    messageDiv.textContent = msg;
    document.body.appendChild(messageDiv);
    setTimeout(() => messageDiv.remove(), 3000);
}