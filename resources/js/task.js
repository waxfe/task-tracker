let taskSelectedUsers = [];

// ========== ОТКРЫТИЕ КАРТОЧКИ ==========
window.openTaskCard = function (taskId) {
    fetch(`/tasks/${taskId}`)
        .then(res => res.json())
        .then(data => {
            renderTaskModal(data);
            window.currentTaskId = taskId;
            document.getElementById('taskModal').classList.remove('hidden');
        })
        .catch(err => {
            console.error(err);
            alert('Не удалось загрузить задачу');
        });
};

window.closeTaskModal = function () {
    document.getElementById('taskModal').classList.add('hidden');

    if (window.location.pathname === '/dashboard' && window.currentProjectId) {
        const currentSortField = currentSort?.field;
        const currentSortDirection = currentSort?.direction;

        fetch(`/dashboard?project_id=${window.currentProjectId}&view=${window.currentView || 'list'}`)
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newTasksHtml = doc.querySelector('.tasks-table-container')?.innerHTML;
                if (newTasksHtml) {
                    document.querySelector('.tasks-table-container').innerHTML = newTasksHtml;

                    if (typeof initSorting === 'function') {
                        initSorting();
                    }

                    if (currentSortField && typeof sortTasks === 'function') {
                        currentSort.field = currentSortField;
                        currentSort.direction = currentSortDirection;
                        sortTasks(currentSortField);
                    }
                }

                const newKanbanHtml = doc.querySelector('.kanban-board')?.innerHTML;
                if (newKanbanHtml) {
                    document.querySelector('.kanban-board').innerHTML = newKanbanHtml;
                }

                if (typeof bindDeleteButtons === 'function') {
                    bindDeleteButtons();
                }
            })
            .catch(err => console.error('Error reloading dashboard:', err));
    }
};

// ========== РЕДАКТИРОВАНИЕ НАЗВАНИЯ ==========
window.editTaskTitle = function () {
    const display = document.getElementById('taskTitleDisplay');
    const input = document.getElementById('taskTitleInput');
    const saveBtn = document.getElementById('taskTitleSaveBtn');

    display.classList.add('hidden');
    input.classList.remove('hidden');
    saveBtn.classList.remove('hidden');
    input.value = display.innerText;
    input.focus();
};

document.getElementById('taskTitleSaveBtn')?.addEventListener('click', () => {
    const input = document.getElementById('taskTitleInput');
    const display = document.getElementById('taskTitleDisplay');
    const saveBtn = document.getElementById('taskTitleSaveBtn');
    const newTitle = input.value.trim();

    if (!newTitle) {
        showMessage('Название не может быть пустым', true);
        return;
    }

    updateTaskField('name', newTitle);

    display.innerText = newTitle;
    display.classList.remove('hidden');
    input.classList.add('hidden');
    saveBtn.classList.add('hidden');
});

document.getElementById('taskTitleInput')?.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const display = document.getElementById('taskTitleDisplay');
        const input = document.getElementById('taskTitleInput');
        const saveBtn = document.getElementById('taskTitleSaveBtn');
        display.classList.remove('hidden');
        input.classList.add('hidden');
        saveBtn.classList.add('hidden');
    }
});

// ========== ОСНОВНОЙ РЕНДЕР ==========
function renderTaskModal(task) {
    window.lastTaskUsers = task.available_users || [];
    document.getElementById('taskTitleDisplay').innerText = task.name;
    const badge = document.getElementById('taskPriorityBadge');
    badge.className = `task-priority-badge priority-${task.priority}`;

    document.getElementById('taskStatusBadge').innerText = translateStatus(task.status);

    const descDiv = document.getElementById('taskDescriptionDisplay');
    descDiv.innerText = task.description || 'Нет описания';
    const descInput = document.getElementById('taskDescriptionInput');
    descInput.value = task.description || '';

    document.getElementById('taskStatusSelect').value = task.status;


    const prioritySelect = document.getElementById('taskPrioritySelect');
    prioritySelect.value = task.priority;
    prioritySelect.style.color = getPriorityColor(task.priority);

    document.getElementById('taskDueDateInput').value = task.due_date || '';
    document.getElementById('taskCreatedAtStatic').innerText = task.created_at || task.creation_date || '—';
    document.getElementById('taskCreatedDate').innerText = task.created_at || '—';

    // Исполнители
    taskSelectedUsers = (task.available_users || [])
        .filter(u => task.user_ids && task.user_ids.includes(u.id))
        .map(u => ({
            id: u.id,
            name: u.name
        }));

    renderTaskSelectedUsers();
    loadTaskAssignees(task.available_users || []);

    // Комментарии
    if (task.comments) renderComments([...task.comments].reverse());

    // История
    if (task.history && Array.isArray(task.history)) {
        renderHistory([...task.history].reverse());
    } else {
        fetchTaskHistory(task.id);
    }

    // AI-рекомендации
    renderAiRecommendations(task.ai_recommendations || []);
}
function renderTaskSelectedUsers() {
    const container = document.getElementById('taskSelectedUsersContainer');
    if (!container) return;

    container.innerHTML = '';

    taskSelectedUsers.forEach(user => {
        const tag = document.createElement('div');
        tag.className = 'selected-user-tag';
        tag.innerHTML = `
            <span>${escapeHtml(user.name)}</span>
            <button type="button" class="remove-user" data-id="${user.id}">✕</button>
        `;
        container.appendChild(tag);
    });

    container.querySelectorAll('.remove-user').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const id = parseInt(e.currentTarget.dataset.id);

            taskSelectedUsers = taskSelectedUsers.filter(u => u.id !== id);

            renderTaskSelectedUsers();
            loadTaskAssignees(window.lastTaskUsers || []);

            syncTaskUsers();
        });
    });
}

function getPriorityColor(priority) {
    switch (priority) {
        case 'high': return '#DC2626';
        case 'medium': return '#F59E0B';
        case 'low': return '#10B981';
        default: return '#000';
    }
}

document.getElementById('taskPrioritySelect')?.addEventListener('change', function () {
    this.style.color = getPriorityColor(this.value);
});

document.addEventListener('click', function (e) {
    const isInsideModal = !!e.target.closest('.task-modal-container');
    const isOnMenuBtn = !!e.target.closest('.comment-menu-btn');
    const isOnDropdown = !!e.target.closest('.comment-dropdown');
    const isOnRemoveBtn = !!e.target.closest('.remove-user');
    const isOnModalOverlay = !!e.target.closest('.modal-overlay');

    if (isInsideModal && !isOnMenuBtn && !isOnDropdown && !isOnRemoveBtn) {
        document.querySelectorAll('.comment-dropdown').forEach(dd => dd.classList.add('hidden'));
    }

    if (isOnModalOverlay && !isInsideModal) {
        closeTaskModal();
    }
});

// ========== КОММЕНТАРИИ ==========
function renderComments(comments) {
    const container = document.getElementById('commentsList');
    if (!comments.length) {
        container.innerHTML = '<div class="placeholder-text">Нет комментариев</div>';
        return;
    }
    container.innerHTML = comments.map(c => `
        <div class="comment-item" data-comment-id="${c.id}">
            <div class="comment-header">
                <i class="fas fa-user-circle"></i>
                <span class="comment-user">${escapeHtml(c.user.name)}</span>
                <span class="comment-date">${c.created_at}</span>
                <div class="comment-menu-wrapper">
                    <button class="comment-menu-btn" data-comment-id="${c.id}">⋮</button>
                    <div class="comment-dropdown hidden" id="commentDropdown-${c.id}">
                        <button class="dropdown-item edit-comment" data-id="${c.id}">
                            <i class="fas fa-pen"></i> Редактировать
                        </button>
                        <button class="dropdown-item delete-comment" data-id="${c.id}">
                            <i class="fas fa-trash"></i> Удалить
                        </button>
                    </div>
                </div>
            </div>
            <div class="comment-text">${escapeHtml(c.text)}</div>
        </div>
    `).join('');

    attachCommentDropdownEvents();
}

window.editComment = function (commentId) {
    const commentDiv = document.querySelector(`.comment-item[data-comment-id="${commentId}"]`);
    const textSpan = commentDiv.querySelector('.comment-text');
    const oldText = textSpan.innerText;

    const textarea = document.createElement('textarea');
    textarea.value = oldText;
    textarea.classList.add('edit-comment-textarea');
    textSpan.replaceWith(textarea);

    const saveBtn = document.createElement('button');
    saveBtn.innerText = 'Сохранить';
    saveBtn.classList.add('btn-sm', 'save-comment-btn');
    commentDiv.appendChild(saveBtn);

    textarea.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            textarea.replaceWith(textSpan);
            saveBtn.remove();
        }
    });

    saveBtn.onclick = () => {
        const newText = textarea.value.trim();
        if (!newText) return;

        fetch(`/tasks/${window.currentTaskId}/comments/${commentId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ text: newText })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const newSpan = document.createElement('div');
                    newSpan.className = 'comment-text';
                    newSpan.innerText = newText;
                    textarea.replaceWith(newSpan);
                    saveBtn.remove();

                    const dateSpan = commentDiv.querySelector('.comment-date');
                    if (dateSpan && data.comment.updated_at) {
                        dateSpan.innerText = data.comment.updated_at;
                    }

                    showMessage('Комментарий обновлён', false);
                } else {
                    showMessage(data.message || 'Ошибка', true);
                    textarea.replaceWith(textSpan);
                    saveBtn.remove();
                }
            })
            .catch(err => {
                console.error(err);
                showMessage('Ошибка соединения', true);
                textarea.replaceWith(textSpan);
                saveBtn.remove();
            });
    };

    textarea.focus();
    setTimeout(() => {
        saveBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 50);
};

let pendingCommentId = null;

window.deleteComment = function (commentId) {
    pendingCommentId = commentId;
    document.getElementById('deleteCommentModal').classList.remove('hidden');
};

window.closeDeleteCommentModal = function () {
    document.getElementById('deleteCommentModal').classList.add('hidden');
    pendingCommentId = null;
};

document.getElementById('confirmDeleteCommentBtn')?.addEventListener('click', function () {
    if (!pendingCommentId) return;

    const taskId = window.currentTaskId;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    fetch(`/tasks/${taskId}/comments/${pendingCommentId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const commentElement = document.querySelector(`.comment-item[data-comment-id="${pendingCommentId}"]`);
                if (commentElement) commentElement.remove();
                showMessage('Комментарий удалён', false);
            } else {
                showMessage(data.message || 'Ошибка удаления', true);
            }
            closeDeleteCommentModal();
        })
        .catch(err => {
            console.error(err);
            showMessage('Ошибка соединения', true);
            closeDeleteCommentModal();
        });
});

function attachCommentDropdownEvents() {
    document.querySelectorAll('.comment-menu-btn').forEach(btn => {
        btn.removeEventListener('click', handleCommentMenuClick);
        btn.addEventListener('click', handleCommentMenuClick);
    });

    document.querySelectorAll('.edit-comment').forEach(btn => {
        btn.removeEventListener('click', handleEditComment);
        btn.addEventListener('click', handleEditComment);
    });

    document.querySelectorAll('.delete-comment').forEach(btn => {
        btn.removeEventListener('click', handleDeleteComment);
        btn.addEventListener('click', handleDeleteComment);
    });
}

function handleCommentMenuClick(e) {
    e.stopPropagation();
    const commentId = this.dataset.commentId;
    const dropdown = document.getElementById(`commentDropdown-${commentId}`);

    document.querySelectorAll('.comment-dropdown').forEach(dd => {
        if (dd.id !== `commentDropdown-${commentId}`) dd.classList.add('hidden');
    });

    dropdown.classList.toggle('hidden');

    if (!dropdown.classList.contains('hidden')) {
        const btn = e.currentTarget;
        const wrapper = document.querySelector('.comments-list-wrapper');

        const positionDropdown = () => {
            const rect = btn.getBoundingClientRect();
            dropdown.style.position = 'fixed';
            dropdown.style.top = `${rect.bottom + 4}px`;
            dropdown.style.right = `${window.innerWidth - rect.right}px`;
            dropdown.style.left = 'auto';
        };

        if (wrapper) {
            const btnRect = btn.getBoundingClientRect();
            const wrapperRect = wrapper.getBoundingClientRect();
            const isVisible = btnRect.top >= wrapperRect.top && btnRect.bottom <= wrapperRect.bottom;

            if (!isVisible) {
                const commentItem = document.querySelector(`.comment-item[data-comment-id="${commentId}"]`);
                commentItem?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });

                wrapper.addEventListener('scrollend', positionDropdown, { once: true });

                setTimeout(positionDropdown, 300);
                return;
            }
        }

        positionDropdown();
    }
}

function handleEditComment(e) {
    e.stopPropagation();
    const commentId = this.dataset.id;
    document.getElementById(`commentDropdown-${commentId}`)?.classList.add('hidden');
    editComment(commentId);
}

function handleDeleteComment(e) {
    e.stopPropagation();
    const commentId = this.dataset.id;
    document.getElementById(`commentDropdown-${commentId}`)?.classList.add('hidden');
    deleteComment(commentId);
}

// ========== ИСТОРИЯ ==========
function renderHistory(history) {
    const container = document.getElementById('taskHistoryList');
    if (!history.length) {
        container.innerHTML = '<div class="placeholder-text">История пуста</div>';
        return;
    }
    container.innerHTML = history.map(h => {
        const fieldName = getFieldName(h.changed_field);
        const oldVal = translateField(h.changed_field, h.old_value);
        const newVal = translateField(h.changed_field, h.new_value);
        return `
        <div class="history-item">
            <div class="history-user">${escapeHtml(h.user.name)}</div>
            <div class="history-date">${h.change_date}</div>
            <div class="history-text">
                ${h.name ? `в задаче «${escapeHtml(h.name)}» ` : ''}
                Изменил(а) ${fieldName}: 
                "${oldVal}" → "${newVal}"
            </div>
        </div>
    `;
    }).join('');
}

function translateField(field, value) {
    if (field === 'name') {
        return value || '-';
    }
    if (field === 'priority') {
        const map = { low: 'Низкий', medium: 'Средний', high: 'Высокий' };
        return map[value] || value;
    }
    if (field === 'status') {
        const map = { todo: 'К выполнению', in_progress: 'В работе', done: 'Завершено' };
        return map[value] || value;
    }
    if (field === 'due_date') {
        return value || '—';
    }
    if (field === 'user_ids') {
        if (!value) return 'никто';
        const names = value.split(',').map(id => {
            const user = window.lastTaskUsers?.find(u => u.id == id);
            return user?.name || id;
        });
        return names.join(', ');
    }
    return value || '—';
}

function getFieldName(field) {
    const map = {
        name: 'название',
        priority: 'приоритет',
        status: 'статус',
        due_date: 'срок выполнения',
        description: 'описание',
        user_ids: 'исполнители'
    };
    return map[field] || field;
}

// ========== AI-РЕКОМЕНДАЦИИ ==========
function renderAiRecommendations(recommendations) {
    const container = document.getElementById('aiRecommendationsList');
    if (!container) return;

    if (!recommendations || recommendations.length === 0) {
        container.innerHTML = '<div class="placeholder-text">Нажмите «Запрос», чтобы получить рекомендации</div>';
        return;
    }

    let items = recommendations;
    if (typeof recommendations[0] === 'string') {
        items = recommendations;
    } else if (recommendations[0] && typeof recommendations[0] === 'object' && recommendations[0].text) {
        items = recommendations.map(r => r.text);
    }

    container.innerHTML = items.map(text => `
        <div class="ai-card-simple">
            <div class="ai-text">${escapeHtml(text)}</div>
        </div>
    `).join('');
}

// ========== ОБНОВЛЕНИЕ ПОЛЕЙ (AJAX) ==========

// Приоритет
document.getElementById('taskPrioritySelect')?.addEventListener('change', function () {
    updateTaskField('priority', this.value);
});

// Статус
document.getElementById('taskStatusSelect')?.addEventListener('change', function () {
    updateTaskField('status', this.value);
});

// Срок выполнения
document.getElementById('taskDueDateInput')?.addEventListener('change', function () {
    updateTaskField('due_date', this.value);
});

// Исполнители
document.getElementById('taskAssigneeSelect')?.addEventListener('change', function () {
    const userId = parseInt(this.value);
    if (!userId) return;

    const user = window.lastTaskUsers.find(u => u.id === userId);

    if (user && !taskSelectedUsers.some(s => s.id === userId)) {
        taskSelectedUsers.push({
            id: user.id,
            name: user.name
        });

        renderTaskSelectedUsers();
        loadTaskAssignees(window.lastTaskUsers);

        syncTaskUsers();

        this.value = '';
    }
});

function syncTaskUsers() {
    updateTaskField('user_ids', taskSelectedUsers.map(u => u.id));
}
function loadTaskAssignees(allUsers) {
    const select = document.getElementById('taskAssigneeSelect');
    if (!select) return;

    const available = allUsers.filter(u =>
        !taskSelectedUsers.some(s => s.id === u.id)
    );

    select.innerHTML = '<option value="">-- Выберите исполнителя --</option>';

    available.forEach(user => {
        const option = document.createElement('option');
        option.value = user.id;
        option.textContent = user.name;
        select.appendChild(option);
    });
}

function updateTaskField(field, value) {
    const taskId = window.currentTaskId;
    const payload = { [field]: value };

    fetch(`/tasks/${taskId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(payload)
    })
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP ${res.status}`);
            }
            return res.json();
        })
        .then(data => {
            if (data.success && data.task) {
                updateTaskInDashboard(data.task);

                const badge = document.getElementById('taskPriorityBadge');
                if (badge) badge.className = `task-priority-badge priority-${data.task.priority}`;

                const statusBadge = document.getElementById('taskStatusBadge');
                if (statusBadge) statusBadge.innerText = translateStatus(data.task.status);

                if (data.task.history) {
                    renderHistory([...data.task.history].reverse());
                }

                showMessage(data.message || 'Обновлено', false);
            } else {
                showMessage(data.message || 'Ошибка', true);
                openTaskCard(taskId);
            }
        })
        .catch(err => {
            console.error(err);
            showMessage('Ошибка соединения', true);
        });
}

function fetchTaskHistory(taskId) {
    fetch(`/tasks/${taskId}/history`)
        .then(res => res.json())
        .then(data => {
            if (data.history) {
                renderHistory([...data.history].reverse());
            }
        })
        .catch(err => console.error('Ошибка загрузки истории:', err));
}

// ========== ОПИСАНИЕ ==========
const descDiv = document.getElementById('taskDescriptionDisplay');
const descInput = document.getElementById('taskDescriptionInput');
const descSaveBtn = document.getElementById('taskSaveDescriptionBtn');

descDiv?.addEventListener('click', () => {
    descDiv.classList.add('hidden');
    descInput.classList.remove('hidden');
    descSaveBtn.classList.remove('hidden');
    descInput.focus();
});

descSaveBtn?.addEventListener('click', () => {
    const newDesc = descInput.value.trim();

    updateTaskField('description', newDesc);

    descDiv.innerText = newDesc || 'Нет описания';
    descDiv.classList.remove('hidden');
    descInput.classList.add('hidden');
    descSaveBtn.classList.add('hidden');
});

// ========== КОММЕНТАРИИ ==========
const commentTextarea = document.getElementById('newCommentText');
const addCommentBtn = document.getElementById('addCommentBtn');

commentTextarea?.addEventListener('input', function () {
    if (this.value.trim()) {
        addCommentBtn.classList.remove('hidden');
    } else {
        addCommentBtn.classList.add('hidden');
    }
});

document.getElementById('addCommentBtn')?.addEventListener('click', () => {
    const text = document.getElementById('newCommentText').value.trim();
    if (!text) return;

    fetch(`/tasks/${window.currentTaskId}/comments`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ text })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('newCommentText').value = '';
                addCommentBtn.classList.add('hidden');
                openTaskCard(window.currentTaskId);
            } else {
                showMessage(data.message || 'Ошибка', true);
            }
        });
});

// ========== AI-ЗАПРОС ==========
document.getElementById('requestAiRecommendBtn')?.addEventListener('click', () => {
    const taskId = window.currentTaskId;
    if (!taskId) {
        showMessage('ID задачи не найден', true);
        return;
    }

    const container = document.getElementById('aiRecommendationsList');
    container.innerHTML = '<div class="loading-spinner">🤔 Анализируем задачу...</div>';

    fetch(`/tasks/${taskId}/ai-analyze`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.analysis) {
                renderAiRecommendations(data.analysis);
                showMessage('Рекомендации получены', false);
            } else {
                container.innerHTML = '<div class="placeholder-text error">Не удалось получить рекомендации</div>';
                showMessage(data.message || 'Ошибка получения рекомендаций', true);
            }
        })
        .catch(error => {
            console.error('AI error:', error);
            container.innerHTML = '<div class="placeholder-text error">Ошибка соединения</div>';
            showMessage('Ошибка соединения', true);
        });
});

// ========== ВСПОМОГАТЕЛЬНЫЕ ==========
function translateStatus(status) {
    const map = { todo: 'К выполнению', in_progress: 'В работе', done: 'Завершено' };
    return map[status] || status;
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

function showMessage(msg, isError = false) {
    if (typeof window.showMemberMessage === 'function') {
        window.showMemberMessage(msg, isError);
    } else {
        alert(msg);
    }
}


// ================== СОЗДАНИЕ ЗАДАЧИ ========================

let selectedUsers = [];

// Открытие модалки создания задачи
window.openCreateTaskModal = function () {
    const modal = document.getElementById('createTaskModal');
    if (!modal) return;

    selectedUsers = [];

    if (window.currentUserId && window.currentUserName) {
        selectedUsers.push({ id: window.currentUserId, name: window.currentUserName });
    }

    renderSelectedUsers();
    loadAssigneeSelect();

    if (window.presetTaskStatus) {
        const statusSelect = document.getElementById('newTaskStatus');
        if (statusSelect) statusSelect.value = window.presetTaskStatus;
        window.presetTaskStatus = null;
    }

    document.getElementById('newTaskName').value = '';
    document.getElementById('newTaskDescription').value = '';
    document.getElementById('newTaskStatus').value = 'todo';
    document.getElementById('newTaskPriority').value = 'medium';
    document.getElementById('newTaskDueDate').value = '';

    modal.classList.remove('hidden');
};

window.closeCreateTaskModal = function () {
    const modal = document.getElementById('createTaskModal');
    if (modal) modal.classList.add('hidden');
};

function loadAssigneeSelect() {
    const select = document.getElementById('newTaskUsersSelect');
    if (!select) return;

    const projectId = window.currentProjectId;
    if (!projectId) return;

    fetch(`/projects/${projectId}/users`)
        .then(res => res.json())
        .then(users => {
            const available = users.filter(u => !selectedUsers.some(s => s.id === u.id));
            select.innerHTML = '<option value="">-- Выберите исполнителя --</option>';
            available.forEach(user => {
                const option = document.createElement('option');
                option.value = user.id;
                option.textContent = user.name;
                select.appendChild(option);
            });
        });
}

function renderSelectedUsers() {
    const container = document.getElementById('selectedUsersContainer');
    if (!container) return;

    container.innerHTML = '';
    selectedUsers.forEach(user => {
        const tag = document.createElement('div');
        tag.className = 'selected-user-tag';
        tag.innerHTML = `
            <span>${escapeHtml(user.name)}</span>
            <button type="button" class="remove-user" data-id="${user.id}">✕</button>
        `;
        container.appendChild(tag);
    });

    document.querySelectorAll('.remove-user').forEach(btn => {
        btn.removeEventListener('click', handleRemoveUser);
        btn.addEventListener('click', handleRemoveUser);
    });
}

function handleRemoveUser(e) {
    e.stopPropagation();
    const id = parseInt(e.currentTarget.getAttribute('data-id'));
    selectedUsers = selectedUsers.filter(u => u.id !== id);
    renderSelectedUsers();
    loadAssigneeSelect();
}

document.getElementById('newTaskUsersSelect')?.addEventListener('change', function () {
    const userId = parseInt(this.value);
    if (!userId) return;

    const projectId = window.currentProjectId;

    fetch(`/projects/${projectId}/users`)
        .then(res => res.json())
        .then(users => {
            const user = users.find(u => u.id === userId);

            if (user && !selectedUsers.some(s => s.id === userId)) {
                selectedUsers.push({ id: user.id, name: user.name });

                renderSelectedUsers();
                loadAssigneeSelect();

                this.value = "";
            }
        });
});

// Создание задачи
document.getElementById('submitNewTaskBtn')?.addEventListener('click', function () {
    const name = document.getElementById('newTaskName').value.trim();
    if (!name) {
        alert('Название задачи обязательно');
        return;
    }

    const payload = {
        name: name,
        description: document.getElementById('newTaskDescription').value,
        status: document.getElementById('newTaskStatus').value,
        priority: document.getElementById('newTaskPriority').value,
        due_date: document.getElementById('newTaskDueDate').value,
        project_id: window.currentProjectId,
        user_ids: selectedUsers.map(u => u.id)
    };

    fetch('/tasks', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(payload)
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showMessage('Задача создана', false);
                closeCreateTaskModal();
                if (window.currentProjectId) {
                    window.location.href = `/dashboard?project_id=${window.currentProjectId}&view=${window.currentView || 'list'}`;
                } else {
                    location.reload();
                }
            } else {
                showMessage(data.message || 'Ошибка создания', true);
            }
        })
        .catch(err => {
            console.error(err);
            showMessage('Ошибка соединения', true);
        });
});

function updateTaskInDashboard(task) {
    const tableRow = document.querySelector(`.tasks-table tbody tr[data-task-id="${task.id}"]`);
    if (tableRow) {
        tableRow.querySelector('.task-name').innerText = task.name;

        // Исполнители
        const assigneeCell = tableRow.querySelector('td:nth-child(2)');
        assigneeCell.innerHTML = task.user_ids.map(id => {
            const user = task.available_users.find(u => u.id === id);
            return `<span class="assignee">${user?.name || ''}</span>`;
        }).join('');

        // Статус
        const statusBadge = tableRow.querySelector('.status-badge');
        statusBadge.className = `status-badge status-${task.status}`;
        statusBadge.innerText = translateStatus(task.status);

        // Приоритет
        const priorityBadge = tableRow.querySelector('.priority-badge');
        priorityBadge.className = `priority-badge priority-${task.priority}`;
        priorityBadge.innerText = translatePriority(task.priority);

        // Дата
        const dueDateCell = tableRow.querySelector('td:nth-child(5)');
        dueDateCell.innerText = task.due_date || '—';
        dueDateCell.className = task.due_date && task.due_date < new Date().toISOString().split('T')[0] ? 'overdue' : '';

        // Дата обновления
        const updatedCell = tableRow.querySelector('td:nth-child(6)');
        updatedCell.innerText = new Date().toLocaleDateString('ru-RU');
    }

    const kanbanCard = document.querySelector(`.kanban-card[data-task-id="${task.id}"]`);
    if (kanbanCard) {
        kanbanCard.querySelector('.card-title').innerText = task.name;
        const prioritySpan = kanbanCard.querySelector('.priority-badge, .priority-low, .priority-medium, .priority-high');
        if (prioritySpan) {
            prioritySpan.className = `priority-${task.priority}`;
            prioritySpan.innerText = task.priority;
        }
        const dueSpan = kanbanCard.querySelector('.due-date');
        if (dueSpan) dueSpan.innerText = task.due_date || '—';

        const newColumn = document.querySelector(`.kanban-column[data-status="${task.status}"] .kanban-tasks`);
        if (newColumn && !newColumn.contains(kanbanCard)) {
            kanbanCard.remove();
            newColumn.appendChild(kanbanCard);
        }
    }
}

function translatePriority(priority) {
    const map = { low: 'Низкий', medium: 'Средний', high: 'Высокий' };
    return map[priority] || priority;
}

// ======================  УДАЛЕНИЕ ЗАДАЧИ ===========================

let pendingDeleteTaskId = null;

window.openDeleteTaskModal = function (taskId) {
    console.log('openDeleteTaskModal', taskId);
    const modal = document.getElementById('deleteTaskModal');
    if (!modal) {
        console.error('deleteTaskModal not found');
        return;
    }
    pendingDeleteTaskId = taskId;
    modal.classList.remove('hidden');
};

window.closeDeleteTaskModal = function () {
    const modal = document.getElementById('deleteTaskModal');
    if (modal) {
        modal.classList.add('hidden');
    }
    pendingDeleteTaskId = null;
};

function executeDeleteTask() {
    if (!pendingDeleteTaskId) {
        console.error('No task ID to delete');
        return;
    }

    console.log('Deleting task:', pendingDeleteTaskId);

    fetch(`/tasks/${pendingDeleteTaskId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showMessage('Задача удалена', false);
                closeDeleteTaskModal();

                if (window.currentProjectId) {
                    window.location.href = `/dashboard?project_id=${window.currentProjectId}&view=${window.currentView || 'list'}`;
                } else {
                    location.reload();
                }
            } else {
                showMessage(data.message || 'Ошибка удаления', true);
                closeDeleteTaskModal();
            }
        })
        .catch(err => {
            console.error('Delete error:', err);
            showMessage('Ошибка соединения', true);
            closeDeleteTaskModal();
        });
}

function bindDeleteButtons() {
    document.querySelectorAll('.delete-task-btn').forEach(btn => {
        btn.removeEventListener('click', handleDeleteClick);
        btn.addEventListener('click', handleDeleteClick);
    });
}

function handleDeleteClick(e) {
    e.stopPropagation();
    e.preventDefault();

    const taskId = this.dataset.taskId;
    if (taskId) {
        openDeleteTaskModal(taskId);
    }
}

function initDeleteConfirmButton() {
    const confirmBtn = document.getElementById('confirmDeleteTaskBtn');
    if (confirmBtn) {
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

        newConfirmBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            executeDeleteTask();
        });
    } else {
        setTimeout(initDeleteConfirmButton, 100);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    bindDeleteButtons();
    initDeleteConfirmButton();
});

window.refreshDeleteHandlers = function () {
    bindDeleteButtons();
    initDeleteConfirmButton();
};