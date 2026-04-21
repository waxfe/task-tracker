<?php

namespace Database\Seeders;

use App\Models\AiInteraction;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class AiInteractionsTableSeeder extends Seeder
{
    public function run(): void
    {
        $ivan = User::find(1);
        $maria = User::find(2);
        $project1 = Project::find(1);
        $task1 = Task::find(1);
        $task2 = Task::find(2);
        $task3 = Task::find(3);

        // ===== СЦЕНАРИЙ 1: Кнопка "AI-анализ" в карточке задачи (без свободного ввода) =====
        AiInteraction::create([
            'user_id' => $ivan->id,
            'project_id' => $project1->project_id,
            'task_id' => $task1->task_id,
            'request_type' => 'analyze_task',
            'input_data' => json_encode([
                'type' => 'button_analysis',
                'context' => [
                    'task_name' => $task1->name,
                    'description' => $task1->description,
                    'status' => $task1->status,
                    'priority' => $task1->priority,
                    'due_date' => $task1->due_date,
                ]
            ]),
            'output_data' => json_encode([
                'recommendations' => [
                    ['type' => 'priority', 'suggestion' => 'medium', 'reason' => 'Задача выполнена'],
                    ['type' => 'next_steps', 'suggestion' => 'Перейти к аутентификации']
                ]
            ]),
            'request_date' => now(),
        ]);

        // ===== СЦЕНАРИЙ 2: Кнопка "AI-анализ" на странице проекта =====
        AiInteraction::create([
            'user_id' => $maria->id,
            'project_id' => $project1->project_id,
            'task_id' => null,
            'request_type' => 'analyze_project',
            'input_data' => json_encode([
                'type' => 'button_analysis',
                'context' => [
                    'project_name' => $project1->name,
                    'total_tasks' => 3,
                    'completed_tasks' => 1,
                    'in_progress_tasks' => 1,
                ]
            ]),
            'output_data' => json_encode([
                'completion_percentage' => 33,
                'risk_level' => 'low',
                'recommendations' => ['Следить за сроками задачи "Аутентификация"']
            ]),
            'request_date' => now(),
        ]);

        // ===== СЦЕНАРИЙ 3: ЧАТ с ИИ (свободный текст) - вопрос о задаче =====
        AiInteraction::create([
            'user_id' => $ivan->id,
            'project_id' => $project1->project_id,
            'task_id' => $task2->task_id,
            'request_type' => 'chat',
            'input_data' => json_encode([
                'type' => 'chat_message',
                'user_message' => 'Как мне лучше разбить эту задачу на подзадачи?',
                'context' => [
                    'task_name' => $task2->name,
                    'description' => $task2->description,
                ]
            ]),
            'output_data' => json_encode([
                'type' => 'chat_response',
                'assistant_message' => 'Рекомендую разбить на 4 подзадачи: 
1. Установить Laravel Breeze (1 час)
2. Настроить страницу регистрации (2 часа)
3. Настроить страницу логина (1 час)
4. Добавить поле registration_date (0.5 часа)',
                'suggested_subtasks' => [
                    ['name' => 'Установить Breeze', 'hours' => 1],
                    ['name' => 'Страница регистрации', 'hours' => 2],
                    ['name' => 'Страница логина', 'hours' => 1],
                    ['name' => 'Поле registration_date', 'hours' => 0.5],
                ]
            ]),
            'request_date' => now(),
        ]);

        // ===== СЦЕНАРИЙ 4: ЧАТ с ИИ - общий вопрос о проекте =====
        AiInteraction::create([
            'user_id' => $maria->id,
            'project_id' => $project1->project_id,
            'task_id' => null,
            'request_type' => 'chat',
            'input_data' => json_encode([
                'type' => 'chat_message',
                'user_message' => 'Какой у нас прогресс по проекту? Что нужно сделать срочно?',
                'context' => [
                    'project_name' => $project1->name,
                ]
            ]),
            'output_data' => json_encode([
                'type' => 'chat_response',
                'assistant_message' => 'Прогресс проекта: 33% (1 из 3 задач выполнена). 
Срочно: задача "Реализовать аутентификацию" должна быть завершена до 25.04.2026. 
Рекомендую начать работу над ней сегодня.',
                'urgent_tasks' => ['Реализовать аутентификацию'],
                'completion_forecast' => '2026-05-05'
            ]),
            'request_date' => now(),
        ]);

        // ===== СЦЕНАРИЙ 5: ЧАТ с ИИ - декомпозиция задачи =====
        AiInteraction::create([
            'user_id' => $ivan->id,
            'project_id' => $project1->project_id,
            'task_id' => $task3->task_id,
            'request_type' => 'chat',
            'input_data' => json_encode([
                'type' => 'chat_message',
                'user_message' => 'Разложи задачу по интеграции ИИ на мелкие шаги',
                'context' => [
                    'task_name' => $task3->name,
                ]
            ]),
            'output_data' => json_encode([
                'type' => 'chat_response',
                'assistant_message' => 'Вот детальная декомпозиция задачи:
1. Получить API-ключ OpenAI (0.5ч)
2. Установить библиотеку (0.5ч)
3. Создать сервис OpenAIService (2ч)
4. Реализовать метод analyzeTask() (3ч)
5. Написать тесты (2ч)
Итого: 8 часов',
                'decomposition' => [
                    ['step' => 1, 'name' => 'Получить API-ключ', 'hours' => 0.5],
                    ['step' => 2, 'name' => 'Установить библиотеку', 'hours' => 0.5],
                    ['step' => 3, 'name' => 'Создать сервис', 'hours' => 2],
                    ['step' => 4, 'name' => 'Метод analyzeTask()', 'hours' => 3],
                    ['step' => 5, 'name' => 'Написать тесты', 'hours' => 2],
                ],
                'total_hours' => 8
            ]),
            'request_date' => now(),
        ]);
    }
}