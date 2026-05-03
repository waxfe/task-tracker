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

window.editTitle = function () {
    const display = document.getElementById('projectTitleDisplay');
    const input = document.getElementById('projectTitleInput');
    const saveBtn = document.getElementById('saveTitleBtn');

    display.classList.add('hidden');
    input.classList.remove('hidden');
    saveBtn.classList.remove('hidden');
    input.focus();
    input.select();
};

window.saveTitle = function () {
    const display = document.getElementById('projectTitleDisplay');
    const input = document.getElementById('projectTitleInput');
    const saveBtn = document.getElementById('saveTitleBtn');
    const newTitle = input.value.trim();

    if (!newTitle) {
        showErrorMessage('Название не может быть пустым');
        return;
    }

    const originalBtnText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Сохранение...';
    saveBtn.disabled = true;

    fetch(`/projects/${window.projectId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ name: newTitle })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                display.textContent = newTitle;
                document.title = `${newTitle} | TaskAssist`;
                display.classList.remove('hidden');
                input.classList.add('hidden');
                saveBtn.classList.add('hidden');
                showSuccessMessage('Название сохранено');
            } else {
                showErrorMessage(data.message || 'Ошибка при сохранении');
            }
        })
        .catch(() => showErrorMessage('Ошибка соединения'))
        .finally(() => {
            saveBtn.innerHTML = originalBtnText;
            saveBtn.disabled = false;
        });
};

document.getElementById('projectTitleInput')?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') saveTitle();
    if (e.key === 'Escape') {
        document.getElementById('projectTitleDisplay').classList.remove('hidden');
        document.getElementById('projectTitleInput').classList.add('hidden');
        document.getElementById('saveTitleBtn').classList.add('hidden');
    }
});

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
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.message || `HTTP ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                display.textContent = newDescription || 'Добавить описание...';
                display.classList.toggle('empty', !newDescription);
                display.classList.remove('hidden');
                input.classList.add('hidden');
                saveBtn.classList.add('hidden');

                showSuccessMessage('Описание сохранено');
            } else {
                showErrorMessage(data.message || 'Ошибка при сохранении');
                display.classList.add('hidden');
                input.classList.remove('hidden');
                saveBtn.classList.remove('hidden');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorMessage(error.message || 'Ошибка соединения с сервером');
            display.classList.add('hidden');
            input.classList.remove('hidden');
            saveBtn.classList.remove('hidden');
        })
        .finally(() => {
            saveBtn.innerHTML = originalBtnText;
            saveBtn.disabled = false;
        });
};

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

document.addEventListener('click', function (event) {
    const menu = document.getElementById('projectMenu');
    const btn = document.querySelector('.project-menu-btn');
    if (menu && !menu.classList.contains('hidden')) {
        if (!btn?.contains(event.target) && !menu.contains(event.target)) {
            menu.classList.add('hidden');
        }
    }
});

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
                    legend: { display: false }
                }
            },
            plugins: [{
                afterDraw: function (chart) {
                    const ctx = chart.ctx;
                    const width = chart.width;
                    const height = chart.height;
                    ctx.restore();

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
    const tasks = tasksData || [];

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
                        ticks: { stepSize: 1, precision: 0 },
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

    const projectId = window.projectId;

    fetch(`/projects/${projectId}/ai-analyze`, {
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
                const recommendations = Array.isArray(data.analysis) ? data.analysis : [data.analysis];
                const cleaned = recommendations.map(r =>
                    String(r)
                        .replace(/^["'\s]+|["'\s]+$/g, '')
                        .replace(/\\"/g, '"')
                        .trim()
                ).filter(r => r.length > 0);
                container.innerHTML = cleaned.map(r => `<div class="ai-insight">${escapeHtml(r)}</div>`).join('');
            } else {
                container.innerHTML = '<div class="ai-insight error">Не удалось получить рекомендации. Попробуйте позже.</div>';
            }
        })
        .catch(error => {
            console.error('AI Analysis error:', error);
            container.innerHTML = '<div class="ai-insight error">Ошибка соединения. Попробуйте позже.</div>';
        });
};

// ========== УЧАСТНИКИ ==========

let pendingDeleteData = {
    projectId: null,
    userId: null,
    element: null
};

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

window.openConfirmDeleteMemberModal = function (projectId, userId, element) {
    pendingDeleteData = { projectId, userId, element };
    const modal = document.getElementById('confirmDeleteMemberModal');
    if (modal) {
        modal.classList.remove('hidden');
    } else {
        console.error('Modal not found');
        alert('Ошибка: модальное окно не найдено');
    }
};

window.closeConfirmDeleteMemberModal = function () {
    const modal = document.getElementById('confirmDeleteMemberModal');
    if (modal) modal.classList.add('hidden');
    pendingDeleteData = { projectId: null, userId: null, element: null };
};

function executeDeleteMember() {
    const { projectId, userId, element } = pendingDeleteData;

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

window.addMember = function () {
    const modal = document.getElementById('addMemberModal');
    if (modal) modal.classList.remove('hidden');
};

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

                const memberCard = selectElement.closest('.member-card');
                const currentUserId = window.currentUserId;
                const isOwner = window.isOwner;

                const deleteBtn = memberCard.querySelector('.remove-member-btn');

                if (newRole === 'owner') {
                    if (deleteBtn) deleteBtn.remove();
                    if (userId == currentUserId) {
                        selectElement.disabled = true;
                    }
                } else {
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

    const submitBtn = document.querySelector('#addMemberForm button[type="submit"]');
    if (submitBtn) {
        submitBtn.innerHTML = 'Добавить';
        submitBtn.disabled = false;
    }
};

// ========== ИНИЦИАЛИЗАЦИЯ ==========
document.addEventListener('DOMContentLoaded', function () {

    const confirmDeleteProjectBtn = document.getElementById('confirmDeleteProjectBtn');
    if (confirmDeleteProjectBtn) {
        confirmDeleteProjectBtn.addEventListener('click', executeDeleteProject);
    }

    const confirmLeaveProjectBtn = document.getElementById('confirmLeaveProjectBtn');
    if (confirmLeaveProjectBtn) {
        confirmLeaveProjectBtn.addEventListener('click', executeLeaveProject);
    }
    if (typeof window.statsData !== 'undefined') {
        initCharts(window.statsData, window.tasksData || [], window.membersData || []);
    }

    if (window.lastAnalysis && window.lastAnalysis.length > 0) {
        const container = document.getElementById('aiAnalysisContainer');
        const cleaned = window.lastAnalysis.map(r =>
            String(r)
                .replace(/^["'\s]+|["'\s]+$/g, '')
                .replace(/\\"/g, '"')
                .trim()
        ).filter(r => r.length > 0);
        container.innerHTML = cleaned.map(r => `<div class="ai-insight">${escapeHtml(r)}</div>`).join('');
    }

    const confirmBtn = document.getElementById('confirmDeleteMemberBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', executeDeleteMember);
    }

    const form = document.getElementById('addMemberForm');
    if (form) {
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
                            submitBtn.innerHTML = originalText;
                            submitBtn.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        window.showMemberMessage('Ошибка соединения', true);
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    });
            });
        }
    }
});