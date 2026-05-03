<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AI-ассистент | TaskAssist</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/marked/9.1.6/marked.min.js"></script>
    @vite(['resources/css/chat.css', 'resources/js/chat.js'])
</head>

<body>
    <div class="chat-page">
        <div class="chat-container">
            <div class="chat-header">
                <div class="header-left">
                    <h1><i class="fas fa-robot"></i> AI-ассистент</h1>
                    <p class="subtitle">Анализ проектов и задач</p>
                </div>
                <div class="header-right">
                    <div class="context-selector">
                        <span>Анализируется:</span>
                        <select id="contextSelect">
                            <option value="general">💬 Общий чат</option>
                            @foreach($projects as $project)
                                <option value="project_{{ $project->project_id }}">
                                    📁 {{ $project->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button id="clearHistoryBtn" class="clear-history-btn" title="Очистить историю">
                        <i class="fas fa-trash-alt"></i> Очистить
                    </button>
                    <button class="close-btn" onclick="window.location.href='/dashboard'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div class="chat-divider"></div>

            <div class="messages-area" id="messagesArea">
                @foreach($messages as $msg)
                    <div class="message {{ $msg->is_user ? 'user' : 'ai' }}">
                        <div class="message-bubble">
                            <div class="message-header">
                                <span class="sender">{{ $msg->is_user ? 'Вы' : 'AI-ассистент' }}</span>
                                <span class="time">{{ $msg->created_at->format('H:i') }}</span>
                            </div>
                            <div class="message-text {{ !$msg->is_user ? 'ai-response' : '' }}">
                                @if($msg->is_user)
                                    {!! nl2br(e($msg->message)) !!}
                                @else
                                    {!! \Illuminate\Support\Str::markdown($msg->message) !!}
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="input-area">
                <div class="input-wrapper">
                    <textarea id="messageInput" rows="1" placeholder="Задайте вопрос AI-ассистенту..."></textarea>
                    <button id="sendBtn" class="send-btn">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
                <div class="input-hint">
                    AI-ассистент может допускать ошибки. Проверяйте важную информацию.
                </div>
            </div>
        </div>
    </div>

    <script>
        window.currentProjectId = {{ $selectedProjectId ?? 'null' }};
        window.currentUserId = {{ auth()->id() }};
    </script>
    {{-- Модалка подтверждения очистки истории --}}
    <div id="clearHistoryModal" class="modal hidden">
        <div class="modal-overlay" onclick="closeClearHistoryModal()"></div>
        <div class="modal-container" style="max-width: 400px;">
            <div class="modal-header">
                <h3>Очистка истории</h3>
                <button class="modal-close" onclick="closeClearHistoryModal()">&times;</button>
            </div>
            <div class="modal-body" style="padding: 20px;">
                <p>Вы уверены, что хотите очистить всю историю сообщений?</p>
                <p class="text-muted" style="font-size: 13px; color: #64748B;">Действие необратимо. Все сообщения будут
                    удалены.</p>
            </div>
            <div class="modal-footer" style="padding: 16px 20px;">
                <button class="btn-secondary" onclick="closeClearHistoryModal()">Отмена</button>
                <button class="btn-danger" id="confirmClearHistoryBtn">Очистить</button>
            </div>
        </div>
    </div>
</body>

</html>