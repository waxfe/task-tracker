let currentSort = { field: null, direction: 'asc' };

// ========== УНИВЕРСАЛЬНЫЙ МЕНЕДЖЕР ТАБЛИЦ ==========
class TaskTableManager {
    constructor() {
        this.state = {
            sort: { field: 'updated_at', direction: 'desc' },
            search: '',
            currentView: 'list'
        };

        this.elements = {
            tbody: () => document.querySelector('.tasks-table tbody'),
            search: () => document.getElementById('globalSearch')
        };

        this.sortConfig = {
            name: (row) => row.querySelector('.task-name')?.innerText || '',
            assignee: (row) => row.querySelector('td:nth-child(2)')?.innerText || '',
            status: (row) => ({ todo: 1, in_progress: 2, done: 3 }[row.querySelector('.status-badge')?.classList[1]?.split('-')[1]] || 0),
            priority: (row) => ({ low: 1, medium: 2, high: 3 }[row.querySelector('.priority-badge')?.classList[1]?.split('-')[1]] || 0),
            due_date: (row) => {
                const dateStr = row.querySelector('td:nth-child(5)')?.innerText.trim();
                if (!dateStr || dateStr === '—') return '';
                const [day, month, year] = dateStr.split('.');
                return `${year}-${month}-${day}`;
            },
            updated_at: (row) => {
                const dateStr = row.querySelector('td:nth-child(6)')?.innerText.trim();
                if (!dateStr || dateStr === '—') return '';
                const [day, month, year] = dateStr.split('.');
                return `${year}-${month}-${day}`;
            }
        };
    }

    init() {
        this.initSorting();
        this.initSearch();
        this.initDelegation();
    }

    initSorting() {
        document.querySelectorAll('.tasks-table th[data-sort]').forEach(th => {
            th.style.cursor = 'pointer';
            th.onclick = () => this.sort(th.dataset.sort);
        });
        this.sort(this.state.sort.field);
    }

    sort(field) {
        const tbody = this.elements.tbody();
        if (!tbody) return;

        // Обновляем состояние
        this.state.sort.direction = this.state.sort.field === field
            ? (this.state.sort.direction === 'asc' ? 'desc' : 'asc')
            : 'asc';
        this.state.sort.field = field;

        // Сортируем строки
        const rows = Array.from(tbody.querySelectorAll('tr[data-task-id]'));
        const getValue = this.sortConfig[field];

        rows.sort((a, b) => {
            const aVal = getValue(a);
            const bVal = getValue(b);
            return this.state.sort.direction === 'asc'
                ? (aVal > bVal ? 1 : -1)
                : (aVal < bVal ? 1 : -1);
        });

        // Перерисовываем
        rows.forEach(row => tbody.appendChild(row));
        this.updateSortIcons(field);
        this.applySearch();
    }

    updateSortIcons(activeField) {
        document.querySelectorAll('.tasks-table th[data-sort]').forEach(th => {
            const icon = th.querySelector('.sort-icon');
            if (!icon) return;

            if (th.dataset.sort === activeField) {
                icon.className = `sort-icon fas fa-sort-${this.state.sort.direction === 'asc' ? 'up' : 'down'}`;
            } else {
                icon.className = 'sort-icon fas fa-sort';
            }
        });
    }

    initSearch() {
        const searchInput = this.elements.search();
        if (searchInput) {
            searchInput.oninput = (e) => {
                this.state.search = e.target.value.toLowerCase();
                this.applySearch();
            };
        }
    }

    applySearch() {
        if (!this.state.search) {
            document.querySelectorAll('.tasks-table tbody tr').forEach(row => {
                row.style.display = '';
            });
            return;
        }

        document.querySelectorAll('.tasks-table tbody tr').forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(this.state.search) ? '' : 'none';
        });
    }

    initDelegation() {
        // Делегирование событий через один обработчик
        document.querySelector('.tasks-table tbody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr[data-task-id]');
            if (row && !e.target.closest('.delete-task-btn')) {
                openTaskCard(row.dataset.taskId);
            }
        });

        document.querySelector('.kanban-board')?.addEventListener('click', (e) => {
            const card = e.target.closest('.kanban-card[data-task-id]');
            if (card && !e.target.closest('.delete-task-btn')) {
                openTaskCard(card.dataset.taskId);
            }
        });
    }

    refresh() {
        if (this.state.sort.field) {
            this.sort(this.state.sort.field);
        }
    }
}

// Инициализация
const tableManager = new TaskTableManager();

document.addEventListener('DOMContentLoaded', () => {
    tableManager.init();
});

// Глобальные функции 
window.setView = (view) => window.location.href = `/dashboard?view=${view}&project_id=${window.currentProjectId}`;
window.openCreateProjectModal = () => document.getElementById('createProjectModal')?.classList.remove('hidden');
window.openAiChat = () => window.location.href = '/ai-chat';
window.getAiRecommendation = (taskId) => window.location.href = `/tasks/${taskId}/ai`;
window.openProjectSettings = (projectId) => projectId && (window.location.href = `/projects/${projectId}`);
window.openCreateTaskModalWithStatus = (status) => (window.presetTaskStatus = status, openCreateTaskModal());
window.refreshSorting = () => tableManager.refresh();