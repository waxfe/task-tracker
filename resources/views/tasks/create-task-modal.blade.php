<div id="createTaskModal" class="modal hidden">
    <div class="modal-overlay" onclick="closeCreateTaskModal()"></div>
    <div class="modal-container task-modal-container">

        <div class="task-modal-header">
            <div class="task-title-section">
                <h2>Создание задачи</h2>
            </div>
            <button class="modal-close" onclick="closeCreateTaskModal()">&times;</button>
        </div>

        <div class="task-modal-divider"></div>

        <div class="task-modal-grid">
            <div class="task-left-column">
                <div class="task-field-group">
                    <label>Название задачи <span class="required">*</span></label>
                    <input type="text" id="newTaskName" class="task-input" placeholder="Введите название">
                </div>

                <div class="task-field-group">
                    <label>Описание</label>
                    <textarea id="newTaskDescription" rows="4" class="task-input"
                        placeholder="Описание задачи..."></textarea>
                </div>
            </div>

            <div class="task-right-column">
                <h4>Атрибуты задачи</h4>
                <div class="task-attributes">
                    <div class="attr-row">
                        <span class="attr-label">Приоритет</span>
                        <select id="newTaskPriority" class="task-select priority-select">
                            <option value="low">Низкий</option>
                            <option value="medium">Средний</option>
                            <option value="high">Высокий</option>
                        </select>
                    </div>

                    <div class="attr-row">
                        <span class="attr-label">Статус</span>
                        <select id="newTaskStatus" class="task-select">
                            <option value="todo">К выполнению</option>
                            <option value="in_progress">В работе</option>
                            <option value="done">Завершено</option>
                        </select>
                    </div>

                    <div class="attr-row">
                        <span class="attr-label">Исполнитель</span>

                        <div class="assignee-wrapper">
                            <select id="newTaskUsersSelect" class="task-select">
                                <option value="">-- Выберите исполнителя --</option>
                            </select>

                            <div id="selectedUsersContainer" class="selected-users-container"></div>
                        </div>
                    </div>

                    <div class="attr-row">
                        <span class="attr-label">Срок выполнения</span>
                        <input type="date" id="newTaskDueDate" class="task-date-input">
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer" style="padding: 16px 24px 24px;">
            <button class="btn-secondary" onclick="closeCreateTaskModal()">Отмена</button>
            <button id="submitNewTaskBtn" class="btn-primary">Создать задачу</button>
        </div>

    </div>
</div>