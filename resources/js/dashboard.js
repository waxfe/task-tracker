// resources/js/dashboard.js

// Переключение между списком и канбаном
window.setView = function (view) {
    const url = new URL(window.location.href);
    url.searchParams.set('view', view);
    window.location.href = url.toString();
};

// Открыть карточку задачи
window.openTaskCard = function (taskId) {
    window.location.href = `/tasks/${taskId}`;
};

// Открыть создание задачи
window.openCreateTaskModal = function () {
    // TODO: реализовать модальное окно создания задачи
    alert('Создание задачи (будет реализовано)');
};

// Открыть создание проекта
window.openCreateProjectModal = function () {
    const modal = document.getElementById('createProjectModal');
    if (modal) {
        modal.classList.remove('hidden');
    } else {
        alert('Модальное окно не найдено');
    }
};

// Открыть чат с AI
window.openAiChat = function () {
    window.location.href = '/ai-chat';
};

// Получить AI рекомендацию для задачи
window.getAiRecommendation = function (taskId) {
    window.location.href = `/tasks/${taskId}/ai`;
};

// Поиск (базовый функционал)
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('globalSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function (e) {
            const query = e.target.value.toLowerCase();
            const tableRows = document.querySelectorAll('.tasks-table tbody tr');

            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }
});

// Открыть страницу настроек проекта
window.openProjectSettings = function (projectId) {
    if (projectId) {
        window.location.href = `/projects/${projectId}`;
    }
};

// Открыть создание задачи с предвыбранным статусом
window.openCreateTaskModalWithStatus = function (status) {
    // TODO: реализовать модальное окно с передачей статуса
    alert('Создание задачи со статусом: ' + status);
};