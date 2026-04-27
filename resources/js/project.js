// resources/js/project.js

// ========== РЕДАКТИРОВАНИЕ ОПИСАНИЯ ==========
window.editDescription = function () {
    const display = document.getElementById('projectDescriptionDisplay');
    const input = document.getElementById('projectDescriptionInput');
    const saveBtn = document.getElementById('saveDescriptionBtn');

    if (display && input && saveBtn) {
        display.classList.add('hidden');
        input.classList.remove('hidden');
        saveBtn.classList.remove('hidden');
        input.focus();
    }
};

window.saveDescription = function () {
    const input = document.getElementById('projectDescriptionInput');
    const display = document.getElementById('projectDescriptionDisplay');
    const saveBtn = document.getElementById('saveDescriptionBtn');
    const projectId = window.projectId;

    if (!projectId) {
        console.error('Project ID not found');
        return;
    }

    const newDescription = input.value;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // Показываем состояние загрузки
    const originalBtnText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Сохранение...';
    saveBtn.disabled = true;

    fetch(`/projects/${projectId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ description: newDescription })
    })
        .then(response => {
            // Проверяем статус ответа
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.message || `HTTP ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Успешное сохранение
                display.textContent = newDescription || 'Добавить описание...';
                display.classList.toggle('empty', !newDescription);
                display.classList.remove('hidden');
                input.classList.add('hidden');
                saveBtn.classList.add('hidden');

                // Показываем временное уведомление об успехе
                showSuccessMessage('Описание сохранено');
            } else {
                showErrorMessage(data.message || 'Ошибка при сохранении');
                // Возвращаемся в режим редактирования
                display.classList.add('hidden');
                input.classList.remove('hidden');
                saveBtn.classList.remove('hidden');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorMessage(error.message || 'Ошибка соединения с сервером');
            // Возвращаемся в режим редактирования
            display.classList.add('hidden');
            input.classList.remove('hidden');
            saveBtn.classList.remove('hidden');
        })
        .finally(() => {
            saveBtn.innerHTML = originalBtnText;
            saveBtn.disabled = false;
        });
};

// Простое уведомление об успехе
function showSuccessMessage(message) {
    const wrapper = document.querySelector('.project-description-wrapper');
    const msgDiv = document.createElement('div');
    msgDiv.className = 'success-toast';
    msgDiv.textContent = message;
    msgDiv.style.cssText = `
        background-color: #10B981;
        color: white;
        padding: 10px 16px;
        border-radius: 10px;
        font-size: 14px;
        margin-top: 12px;
        animation: fadeOut 3s forwards;
    `;
    wrapper.appendChild(msgDiv);

    setTimeout(() => {
        msgDiv.remove();
    }, 3000);
}

// Простое уведомление об ошибке
function showErrorMessage(message) {
    const wrapper = document.querySelector('.project-description-wrapper');
    const msgDiv = document.createElement('div');
    msgDiv.className = 'error-toast';
    msgDiv.textContent = message;
    msgDiv.style.cssText = `
        background-color: #DC2626;
        color: white;
        padding: 10px 16px;
        border-radius: 10px;
        font-size: 14px;
        margin-top: 12px;
        animation: fadeOut 3s forwards;
    `;
    wrapper.appendChild(msgDiv);

    setTimeout(() => {
        msgDiv.remove();
    }, 3000);
}

// Функция удаления подсветки ошибки
function removeErrorHighlight(element) {
    element.classList.remove('error-highlight');
    const errorMsg = document.querySelector('.field-error-message');
    if (errorMsg) errorMsg.remove();
}

// ========== МОДАЛЬНОЕ ОКНО ПРОЕКТА ==========
window.openCreateProjectModal = function () {
    const modal = document.getElementById('createProjectModal');
    if (modal) {
        modal.classList.remove('hidden');
    }
};

window.closeCreateProjectModal = function () {
    const modal = document.getElementById('createProjectModal');
    const form = document.getElementById('createProjectForm');
    if (modal) {
        modal.classList.add('hidden');
    }
    if (form) {
        form.reset();
    }
};

// ========== МЕНЮ ПРОЕКТА ==========
window.toggleProjectMenu = function () {
    const menu = document.getElementById('projectMenu');
    if (menu) {
        menu.classList.toggle('hidden');
    }
};

// Закрыть меню при клике вне его
document.addEventListener('click', function (event) {
    const menu = document.getElementById('projectMenu');
    const btn = document.querySelector('.project-menu-btn');
    if (menu && !menu.classList.contains('hidden')) {
        if (!btn?.contains(event.target) && !menu.contains(event.target)) {
            menu.classList.add('hidden');
        }
    }
});

// В меню проекта вызываем модалки вместо прямых действий
window.deleteProject = function () {
    openConfirmDeleteProjectModal(window.projectId);
};

window.leaveProject = function () {
    openConfirmLeaveProjectModal(window.projectId);
};

// ========== УДАЛЕНИЕ ПРОЕКТА С МОДАЛКОЙ ==========
let pendingDeleteProjectId = null;

window.openConfirmDeleteProjectModal = function (projectId) {
    pendingDeleteProjectId = projectId;
    const modal = document.getElementById('confirmDeleteProjectModal');
    if (modal) modal.classList.remove('hidden');
};

window.closeConfirmDeleteProjectModal = function () {
    const modal = document.getElementById('confirmDeleteProjectModal');
    if (modal) modal.classList.add('hidden');
    pendingDeleteProjectId = null;
};

function executeDeleteProject() {
    if (!pendingDeleteProjectId) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    fetch(`/projects/${pendingDeleteProjectId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect;
            } else {
                window.showMemberMessage(data.message || 'Ошибка при удалении проекта', true);
                closeConfirmDeleteProjectModal();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            window.showMemberMessage('Ошибка соединения', true);
            closeConfirmDeleteProjectModal();
        });
}

// ========== ВЫХОД ИЗ ПРОЕКТА С МОДАЛКОЙ ==========
let pendingLeaveProjectId = null;

window.openConfirmLeaveProjectModal = function (projectId) {
    pendingLeaveProjectId = projectId;
    const modal = document.getElementById('confirmLeaveProjectModal');
    if (modal) modal.classList.remove('hidden');
};

window.closeConfirmLeaveProjectModal = function () {
    const modal = document.getElementById('confirmLeaveProjectModal');
    if (modal) modal.classList.add('hidden');
    pendingLeaveProjectId = null;
};

function executeLeaveProject() {
    if (!pendingLeaveProjectId) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    fetch(`/projects/${pendingLeaveProjectId}/leave`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect;
            } else {
                window.showMemberMessage(data.message || 'Ошибка при выходе из проекта', true);
                closeConfirmLeaveProjectModal();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            window.showMemberMessage('Ошибка соединения', true);
            closeConfirmLeaveProjectModal();
        });
}

// ========== ГРАФИКИ ==========
function initCharts(statsData, tasksData, membersData) {
    // Проверяем, загружен ли Chart.js
    if (typeof Chart === 'undefined') {
        setTimeout(() => initCharts(statsData, tasksData, membersData), 200);
        return;
    }

    // Проверяем, есть ли данные для графиков
    const hasTasks = statsData.total > 0;
    const chartsGrid = document.querySelector('.charts-grid');

    if (!hasTasks) {
        if (chartsGrid) {
            chartsGrid.innerHTML = `
                <div class="no-data-message">
                    <i class="fas fa-chart-simple"></i>
                    <p>В проекте пока нет задач. Добавьте задачи, чтобы увидеть аналитику.</p>
                </div>
            `;
        }
        return;
    }

    // ===== 1. Диаграмма статусов (donut с цифрой внутри) =====
    const statusChart = document.getElementById('statusDonutChart');
    if (statusChart) {
        const total = statsData.todo + statsData.in_progress + statsData.done;

        new Chart(statusChart, {
            type: 'doughnut',
            data: {
                labels: ['К выполнению', 'В работе', 'Завершено'],
                datasets: [{
                    data: [statsData.todo, statsData.in_progress, statsData.done],
                    backgroundColor: ['#F59E0B', '#3B82F6', '#10B981'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '65%',
                plugins: {
                    tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${ctx.raw} задач` } },
                    legend: { display: false }  // Скрываем легенду, т.к. она выведена отдельно
                }
            },
            plugins: [{
                afterDraw: function (chart) {
                    const ctx = chart.ctx;
                    const width = chart.width;
                    const height = chart.height;
                    ctx.restore();

                    // Увеличиваем размер шрифта для общего числа
                    ctx.font = 'bold 28px "Inter"';
                    ctx.fillStyle = '#1E293B';
                    ctx.textAlign = 'center';
                    ctx.fillText(`${total}`, width / 2, height / 2 + 5);

                    ctx.font = '11px "Inter"';
                    ctx.fillStyle = '#64748B';
                    ctx.fillText('всего задач', width / 2, height / 2 + 30);
                    ctx.save();
                }
            }]
        });
    }

    // ===== 2. Динамика выполнения задач (линейный график) =====
    // Данные из реальных задач (создаем историю по датам)
    const tasks = tasksData || [];

    // Группируем задачи по дате создания/завершения
    const tasksByDate = {};
    tasks.forEach(task => {
        const date = task.created_at ? task.created_at.split('T')[0] : null;
        if (date) {
            if (!tasksByDate[date]) tasksByDate[date] = { created: 0, completed: 0 };
            tasksByDate[date].created++;
            if (task.status === 'done') tasksByDate[date].completed++;
        }
    });

    const sortedDates = Object.keys(tasksByDate).sort();
    const createdData = sortedDates.map(d => tasksByDate[d].created);
    const completedData = sortedDates.map(d => tasksByDate[d].completed);

    // ===== 2. Динамика выполнения задач (линейный график) =====
    const lineChart = document.getElementById('completionLineChart');
    if (lineChart) {
        new Chart(lineChart, {
            type: 'line',
            data: {
                labels: sortedDates.length ? sortedDates : ['Нет данных'],
                datasets: [
                    { label: 'Создано задач', data: createdData.length ? createdData : [0], borderColor: '#3B82F6', backgroundColor: 'rgba(59,130,246,0.1)', tension: 0.4, fill: true },
                    { label: 'Завершено задач', data: completedData.length ? completedData : [0], borderColor: '#10B981', backgroundColor: 'rgba(16,185,129,0.1)', tension: 0.4, fill: true }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        stepSize: 1,
                        ticks: { stepSize: 1, precision: 0 },  // Только целые числа
                        title: { display: true, text: 'Количество задач' }
                    },
                    x: { title: { display: true, text: 'Дата' } }
                }
            }
        });
    }

    // ===== 3. Нагрузка на участников (гистограмма с целыми числами) =====
    const barChart = document.getElementById('userLoadBarChart');
    if (barChart && membersData.length > 0) {
        const userTaskCounts = membersData.map(m => tasksData.filter(t => t.users?.some(u => u.id === m.id)).length);

        new Chart(barChart, {
            type: 'bar',
            data: {
                labels: membersData.map(m => m.name),
                datasets: [{
                    label: 'Количество задач',
                    data: userTaskCounts,
                    backgroundColor: '#3B82F6',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: { beginAtZero: true, stepSize: 1, ticks: { stepSize: 1, precision: 0 }, title: { display: true, text: 'Количество задач' } },
                    x: { title: { display: true, text: 'Участники' } }
                },
                plugins: { tooltip: { callbacks: { label: (ctx) => `${ctx.raw} задач` } } }
            }
        });
    }

    // ===== 4. Приоритеты (гистограмма с целыми числами) =====
    const priorityChart = document.getElementById('priorityBarChart');
    if (priorityChart) {
        new Chart(priorityChart, {
            type: 'bar',
            data: {
                labels: ['Низкий', 'Средний', 'Высокий'],
                datasets: [{
                    label: 'Количество задач',
                    data: [statsData.low_priority, statsData.medium_priority, statsData.high_priority],
                    backgroundColor: ['#6B7280', '#F59E0B', '#EF4444'],
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: { beginAtZero: true, stepSize: 1, ticks: { stepSize: 1, precision: 0 }, title: { display: true, text: 'Количество задач' } },
                    x: { title: { display: true, text: 'Приоритет' } }
                },
                plugins: { tooltip: { callbacks: { label: (ctx) => `${ctx.raw} задач` } } }
            }
        });
    }
}

// ========== AI АНАЛИЗ ==========
window.refreshAIAnalysis = function () {
    const container = document.getElementById('aiAnalysisContainer');
    if (!container) return;

    container.innerHTML = '<div class="loading-spinner">🤔 Анализируем проект...</div>';

    const projectId = document.querySelector('meta[name="project-id"]')?.content;

    // Временная заглушка
    setTimeout(() => {
        const statsOverdue = window.statsData?.overdue || 0;
        const statsTodo = window.statsData?.todo || 0;

        const recommendations = [
            `⚠️ В проекте обнаружено ${statsOverdue} задач с истекшим сроком выполнения. Рекомендуется пересмотреть приоритеты и обновить сроки выполнения.`,
            '📊 Наибольшая нагрузка на участника. Рекомендуется перераспределить задачи для равномерной загрузки команды.',
            `📋 ${statsTodo} задач ожидают начала выполнения. Рекомендуется назначить ответственных и установить сроки.`,
            '✅ Темп выполнения задач позволяет завершить проект в срок при сохранении текущей динамики.'
        ];

        container.innerHTML = recommendations.map(r => `<div class="ai-insight">${r}</div>`).join('');
    }, 500);
};

// ========== УЧАСТНИКИ ==========

// Переменные для хранения данных удаляемого участника
let pendingDeleteData = {
    projectId: null,
    userId: null,
    element: null
};

// Глобальное уведомление
window.showMemberMessage = function (message, isError = false) {
    const oldMsg = document.querySelector('.member-toast-message');
    if (oldMsg) oldMsg.remove();

    const msgDiv = document.createElement('div');
    msgDiv.className = 'member-toast-message';
    msgDiv.textContent = message;
    msgDiv.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 12px 20px;
        border-radius: 10px;
        color: white;
        font-size: 14px;
        z-index: 1000;
        background-color: ${isError ? '#DC2626' : '#10B981'};
        animation: fadeOut 3s forwards;
    `;
    document.body.appendChild(msgDiv);
    setTimeout(() => msgDiv.remove(), 3000);
};

// Открытие модалки подтверждения удаления
window.openConfirmDeleteMemberModal = function (projectId, userId, element) {
    console.log('openConfirmDeleteMemberModal called', projectId, userId, element); // Отладка
    pendingDeleteData = { projectId, userId, element };
    const modal = document.getElementById('confirmDeleteMemberModal');
    if (modal) {
        modal.classList.remove('hidden');
    } else {
        console.error('Modal not found');
        alert('Ошибка: модальное окно не найдено');
    }
};

// Закрытие модалки подтверждения удаления
window.closeConfirmDeleteMemberModal = function () {
    const modal = document.getElementById('confirmDeleteMemberModal');
    if (modal) modal.classList.add('hidden');
    pendingDeleteData = { projectId: null, userId: null, element: null };
};

function executeDeleteMember() {
    const { projectId, userId, element } = pendingDeleteData;
    console.log('executeDeleteMember', projectId, userId, element); // Отладка

    if (!projectId || !userId) {
        console.error('No pending delete data');
        closeConfirmDeleteMemberModal();
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    fetch(`/projects/${projectId}/members/${userId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (element) {
                    const memberCard = element.closest('.member-card');
                    if (memberCard) memberCard.remove();
                }
                window.showMemberMessage(data.message, false);
            } else {
                window.showMemberMessage(data.message || 'Ошибка при удалении', true);
            }
            closeConfirmDeleteMemberModal();
        })
        .catch(error => {
            console.error('Error:', error);
            window.showMemberMessage('Ошибка соединения', true);
            closeConfirmDeleteMemberModal();
        });
}

// Добавление участника в DOM
window.addMemberToDOM = function (user) {
    const membersGrid = document.querySelector('.members-grid');
    if (!membersGrid) return;

    const isOwner = window.isOwner || false;
    const currentUserId = window.currentUserId || null;
    const isCurrentUserOwner = window.isOwner || false;

    // Определяем, показывать ли крестик (только для member, и если текущий пользователь владелец)
    const showDeleteButton = isCurrentUserOwner && user.id != currentUserId;

    const memberCard = document.createElement('div');
    memberCard.className = 'member-card';
    memberCard.innerHTML = `
        <div class="member-avatar"><i class="fas fa-user-circle"></i></div>
        <div class="member-info">
            <div class="member-name">${escapeHtml(user.name)}</div>
            <select class="role-select" data-user-id="${user.id}" 
                ${!isOwner || user.id == currentUserId ? 'disabled' : ''}
                onchange="changeRole(${window.projectId}, ${user.id}, this)">
                <option value="member" selected>Участник</option>
                <option value="owner">Владелец</option>
            </select>
        </div>
        ${showDeleteButton ? `
            <button class="remove-member-btn" 
                onclick="openConfirmDeleteMemberModal(${window.projectId}, ${user.id}, this)">
                <i class="fas fa-times"></i>
            </button>
        ` : ''}
    `;
    membersGrid.appendChild(memberCard);
};

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function (m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

// Добавление участника (открывает модалку)
window.addMember = function () {
    const modal = document.getElementById('addMemberModal');
    if (modal) modal.classList.remove('hidden');
};

// Изменение роли
window.changeRole = function (projectId, userId, selectElement) {
    const newRole = selectElement.value;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    const originalValue = selectElement.value;
    selectElement.disabled = true;

    fetch(`/projects/${projectId}/members/${userId}/role`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ role: newRole })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.showMemberMessage(data.message, false);

                // Обновляем UI в зависимости от новой роли
                const memberCard = selectElement.closest('.member-card');
                const currentUserId = window.currentUserId;
                const isOwner = window.isOwner;

                // Находим кнопку удаления
                const deleteBtn = memberCard.querySelector('.remove-member-btn');

                if (newRole === 'owner') {
                    // Если стал владельцем - удаляем кнопку удаления
                    if (deleteBtn) deleteBtn.remove();
                    // Блокируем select для нового владельца (нельзя изменить свою роль)
                    if (userId == currentUserId) {
                        selectElement.disabled = true;
                    }
                } else {
                    // Если стал участником - добавляем кнопку удаления (если текущий пользователь владелец)
                    if (isOwner && userId != currentUserId && !deleteBtn) {
                        const deleteBtnHtml = document.createElement('button');
                        deleteBtnHtml.className = 'remove-member-btn';
                        deleteBtnHtml.onclick = () => openConfirmDeleteMemberModal(projectId, userId, deleteBtnHtml);
                        deleteBtnHtml.innerHTML = '<i class="fas fa-times"></i>';
                        memberCard.appendChild(deleteBtnHtml);
                    }
                }
            } else {
                window.showMemberMessage(data.message || 'Ошибка при изменении роли', true);
                selectElement.value = originalValue;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            window.showMemberMessage('Ошибка соединения', true);
            selectElement.value = originalValue;
        })
        .finally(() => {
            selectElement.disabled = false;
        });
};

// ========== МОДАЛЬНОЕ ОКНО ДОБАВЛЕНИЯ УЧАСТНИКА ==========
window.openAddMemberModal = function () {
    // Сбрасываем форму и кнопку перед открытием
    const form = document.getElementById('addMemberForm');
    if (form) form.reset();

    const submitBtn = document.querySelector('#addMemberForm button[type="submit"]');
    if (submitBtn) {
        submitBtn.innerHTML = 'Добавить';
        submitBtn.disabled = false;
    }

    const modal = document.getElementById('addMemberModal');
    if (modal) modal.classList.remove('hidden');
};;

window.closeAddMemberModal = function () {
    const modal = document.getElementById('addMemberModal');
    if (modal) modal.classList.add('hidden');

    const form = document.getElementById('addMemberForm');
    if (form) form.reset();

    // Восстанавливаем кнопку в исходное состояние
    const submitBtn = document.querySelector('#addMemberForm button[type="submit"]');
    if (submitBtn) {
        submitBtn.innerHTML = 'Добавить';
        submitBtn.disabled = false;
    }
};

// ========== ИНИЦИАЛИЗАЦИЯ ==========
document.addEventListener('DOMContentLoaded', function () {

    // Кнопка подтверждения удаления проекта
    const confirmDeleteProjectBtn = document.getElementById('confirmDeleteProjectBtn');
    if (confirmDeleteProjectBtn) {
        confirmDeleteProjectBtn.addEventListener('click', executeDeleteProject);
    }

    // Кнопка подтверждения выхода из проекта
    const confirmLeaveProjectBtn = document.getElementById('confirmLeaveProjectBtn');
    if (confirmLeaveProjectBtn) {
        confirmLeaveProjectBtn.addEventListener('click', executeLeaveProject);
    }
    // Переменные для графиков
    if (typeof window.statsData !== 'undefined') {
        initCharts(window.statsData, window.tasksData || [], window.membersData || []);
    }

    // Загрузка AI анализа
    if (document.getElementById('aiAnalysisContainer')) {
        refreshAIAnalysis();
    }

    // Кнопка подтверждения удаления
    const confirmBtn = document.getElementById('confirmDeleteMemberBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', executeDeleteMember);
    }

    // Форма добавления участника
    const form = document.getElementById('addMemberForm');
    if (form) {
        // Убираем старый обработчик, чтобы не дублировать
        const oldHandler = form.getAttribute('data-listener');
        if (!oldHandler) {
            form.setAttribute('data-listener', 'true');

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const email = document.getElementById('memberEmail').value;
                const projectId = window.projectId;
                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Добавление...';
                submitBtn.disabled = true;

                fetch(`/projects/${projectId}/members`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ email: email })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            closeAddMemberModal();
                            window.showMemberMessage(data.message, false);
                            if (data.user) {
                                window.addMemberToDOM(data.user);
                            }
                        } else {
                            window.showMemberMessage(data.message || 'Ошибка при добавлении участника', true);
                            // Восстанавливаем кнопку
                            submitBtn.innerHTML = originalText;
                            submitBtn.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        window.showMemberMessage('Ошибка соединения', true);
                        // Восстанавливаем кнопку
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    });
            });
        }
    }
});