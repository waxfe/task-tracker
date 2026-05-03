<div id="taskModal" class="modal hidden">
    <div class="modal-overlay" onclick="closeTaskModal()"></div>
    <div class="modal-container task-modal-container">

        <div class="task-modal-header">
            <div class="task-title-section">
                <div id="taskTitleDisplay" class="task-title-display" onclick="editTaskTitle()">Загрузка...</div>
                <input type="text" id="taskTitleInput" class="hidden task-title-input" maxlength="255">
                <button id="taskTitleSaveBtn" class="hidden btn-sm">Сохранить</button>
                <span class="task-priority-badge" id="taskPriorityBadge"></span>
                <span class="task-status" id="taskStatusBadge"></span>
                <span class="task-created-date" id="taskCreatedDate">—</span>
            </div>
            <button class="modal-close" onclick="closeTaskModal()">&times;</button>
        </div>

        <div class="task-modal-divider"></div>

        <div class="task-modal-grid">
            <div class="task-left-column">
                {{-- Описание --}}
                <div class="task-field-group">
                    <label>Описание задачи</label>
                    <div id="taskDescriptionDisplay" class="task-description-text">Загрузка...</div>
                    <textarea id="taskDescriptionInput" class="hidden task-description-input"></textarea>
                    <button id="taskSaveDescriptionBtn" class="hidden btn-sm">Сохранить</button>
                </div>

                {{-- Комментарии --}}
                <div class="task-field-group">
                    <label>Комментарии</label>
                    <div class="new-comment-area">
                        <textarea id="newCommentText" rows="2" placeholder="Написать комментарий..."></textarea>
                        <button id="addCommentBtn" class="btn-sm hidden">Добавить</button>
                    </div>
                    <div class="comments-list-wrapper">
                        <div id="commentsList" class="comments-list"></div>
                    </div>
                </div>
            </div>

            <div class="task-right-column">
                <h4>Атрибуты задачи</h4>
                <div class="task-attributes">
                    <div class="attr-row">
                        <span class="attr-label">Приоритет</span>
                        <select id="taskPrioritySelect" class="task-select priority-select">
                            <option value="low">Низкий</option>
                            <option value="medium">Средний</option>
                            <option value="high">Высокий</option>
                        </select>
                    </div>

                    <div class="attr-row">
                        <span class="attr-label">Статус</span>
                        <select id="taskStatusSelect" class="task-select">
                            <option value="todo">К выполнению</option>
                            <option value="in_progress">В работе</option>
                            <option value="done">Завершено</option>
                        </select>
                    </div>

                    <div class="attr-row">
                        <span class="attr-label">Исполнитель</span>

                        <div class="assignee-wrapper">
                            <select id="taskAssigneeSelect" class="task-select"></select>

                            <div id="taskSelectedUsersContainer" class="selected-users-container"></div>
                        </div>
                    </div>

                    <div class="attr-row">
                        <span class="attr-label">Срок выполнения</span>
                        <input type="date" id="taskDueDateInput" class="task-date-input">
                    </div>
                    <div class="attr-row readonly">
                        <span class="attr-label">Дата создания</span>
                        <span id="taskCreatedAtStatic">—</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="task-modal-divider"></div>

        <div class="task-bottom-grid">
            <div class="task-ai-section">
                <div class="section-header">
                    <h3>🤖 AI-рекомендации</h3>
                    <button id="requestAiRecommendBtn" class="btn-sm">Запрос</button>
                </div>
                <div id="aiRecommendationsList" class="ai-recommendations-list">
                    <div class="placeholder-text">Нажмите «Запрос»</div>
                </div>
            </div>

            <div class="task-history-section">
                <h3>История изменений</h3>
                <div id="taskHistoryList" class="history-scroll"></div>
            </div>
        </div>
    </div>
</div>

<div id="deleteTaskModal" class="modal hidden">
    <div class="modal-overlay" onclick="closeDeleteTaskModal()"></div>
    <div class="modal-container" style="max-width: 400px;">
        <div class="modal-header">
            <h3>Удаление задачи</h3>
            <button class="modal-close" onclick="closeDeleteTaskModal()">&times;</button>
        </div>
        <div class="modal-body" style="padding: 20px;">
            <p>Вы уверены, что хотите удалить эту задачу?</p>
            <p class="text-muted" style="font-size: 13px; color: #64748B;">Действие необратимо. Все комментарии и
                история будут удалены.</p>
        </div>
        <div class="modal-footer" style="padding: 16px 20px;">
            <button class="btn-secondary" onclick="closeDeleteTaskModal()">Отмена</button>
            <button class="btn-danger" id="confirmDeleteTaskBtn">Удалить</button>
        </div>
    </div>
</div>

<div id="deleteCommentModal" class="modal hidden">
    <div class="modal-overlay" onclick="closeDeleteCommentModal()"></div>
    <div class="modal-container" style="max-width: 400px;" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3>Удаление комментария</h3>
            <button class="modal-close" onclick="closeDeleteCommentModal()">&times;</button>
        </div>
        <div class="modal-body" style="padding: 20px;">
            <p>Вы уверены, что хотите удалить этот комментарий?</p>
            <p class="text-muted" style="font-size: 13px; color: #64748B;">Действие необратимо.</p>
        </div>
        <div class="modal-footer" style="padding: 16px 20px;">
            <button class="btn-secondary" onclick="closeDeleteCommentModal()">Отмена</button>
            <button class="btn-danger" id="confirmDeleteCommentBtn">Удалить</button>
        </div>
    </div>
</div>