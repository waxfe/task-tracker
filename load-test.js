import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
    vus: 50,
    duration: '30s',
    thresholds: {
        http_req_duration: ['p(95)<3000'],
        http_req_failed: ['rate<0.05'],
    },
};

export default function () {
    // ========== 1. ПОЛУЧЕНИЕ CSRF ТОКЕНА ==========
    const loginPageRes = http.get('http://task-tracker.local/login');
    let csrfToken = '';
    const csrfMatch = loginPageRes.body.match(/name="csrf-token" content="([^"]+)"/);
    if (csrfMatch) {
        csrfToken = csrfMatch[1];
    }

    // ========== 2. АВТОРИЗАЦИЯ ==========
    const loginRes = http.post('http://task-tracker.local/login', {
        email: 'ivan@gmail.com',
        password: 'password',
        _token: csrfToken,
    });

    // Следование за редиректом
    let finalLoginRes = loginRes;
    if (loginRes.status === 302 && loginRes.headers['Location']) {
        finalLoginRes = http.get(loginRes.headers['Location']);
    }

    check(finalLoginRes, {
        '✓ авторизация успешна': (r) => r.status === 200,
    });

    // ========== 3. ПОЛУЧЕНИЕ ДАШБОРДА ==========
    const dashboardRes = http.get('http://task-tracker.local/dashboard');
    let apiCsrfToken = '';
    const apiCsrfMatch = dashboardRes.body.match(/name="csrf-token" content="([^"]+)"/);
    if (apiCsrfMatch) {
        apiCsrfToken = apiCsrfMatch[1];
    }

    check(dashboardRes, {
        '✓ дашборд загружен': (r) => r.status === 200,
    });

    // ========== 4. ПОЛУЧЕНИЕ СПИСКА ПОЛЬЗОВАТЕЛЕЙ ПРОЕКТА (РАБОТАЕТ!) ==========
    const usersRes = http.get('http://task-tracker.local/projects/1/users', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
    });

    check(usersRes, {
        '✓ список пользователей получен': (r) => r.status === 200,
        '✓ данные пользователей в JSON': (r) => {
            try {
                const body = r.json();
                return Array.isArray(body);
            } catch (e) {
                return false;
            }
        },
    });

    // ========== 5. СОЗДАНИЕ ЗАДАЧИ ==========
    const taskPayload = JSON.stringify({
        name: `Нагрузочный тест ${Date.now()}`,
        project_id: 1,
        status: 'todo',
        priority: 'medium',
        user_ids: [1],
    });

    const createTaskRes = http.post(
        'http://task-tracker.local/tasks',
        taskPayload,
        {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': apiCsrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
        }
    );

    let taskId = null;
    if (createTaskRes.status === 201) {
        taskId = createTaskRes.json('task_id');
    }

    check(createTaskRes, {
        '✓ задача создана': (r) => r.status === 201,
    });

    // ========== 6. ПОЛУЧЕНИЕ ДЕТАЛЬНОЙ ИНФОРМАЦИИ О ЗАДАЧЕ ==========
    if (taskId) {
        sleep(0.1);

        const taskDetailRes = http.get(`http://task-tracker.local/tasks/${taskId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        });

        check(taskDetailRes, {
            '✓ детали задачи получены': (r) => r.status === 200,
            '✓ данные задачи корректны': (r) => {
                try {
                    return r.json('id') !== undefined;
                } catch (e) {
                    return false;
                }
            },
        });

        // ========== 7. ОБНОВЛЕНИЕ ЗАДАЧИ ==========
        const updatePayload = JSON.stringify({
            status: 'in_progress',
            priority: 'high',
        });

        const updateTaskRes = http.put(
            `http://task-tracker.local/tasks/${taskId}`,
            updatePayload,
            {
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': apiCsrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            }
        );

        check(updateTaskRes, {
            '✓ задача обновлена': (r) => r.status === 200,
        });
    }

    // ========== 8. ПОЛУЧЕНИЕ УЧАСТНИКОВ ПРОЕКТА ==========
    const membersRes = http.get('http://task-tracker.local/projects/1/users', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
    });

    check(membersRes, {
        '✓ участники проекта получены': (r) => r.status === 200,
    });

    sleep(0.5);
}