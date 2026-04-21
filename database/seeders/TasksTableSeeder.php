<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TasksTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $project1 = Project::find(1); // Разработка веб-приложения
        $project2 = Project::find(2); // Маркетинговая компания
        $project3 = Project::find(3); // Исследование рынка

        Task::create([
            'name' => 'Спроектировать базу данных',
            'description' => 'Разработать ER-диаграмму и создать миграции',
            'status' => 'done',
            'priority' => 'high',
            'due_date' => '2026-04-15',
            'project_id' => $project1->project_id,
        ]);

        Task::create([
            'name' => 'Реализовать аунтефикацию',
            'description' => 'Настроить Laravel Breeze и страницы входа/регистрации',
            'status' => 'in_progress',
            'priority' => 'high',
            'due_date' => '2026-04-15',
            'project_id' => $project1->project_id,
        ]);

        Task::create([
            'name' => 'Интегрировать ИИ-ассисента',
            'description' => 'Подключить API OpenAI и реализовать обработку запросов',
            'status' => 'todo',
            'priority' => 'medium',
            'due_date' => '2026-05-10',
            'project_id' => $project1->project_id,
        ]);

        Task::create([
            'name' => 'Создать контент-план',
            'description' => 'Разработать план публикаций на месяц',
            'status' => 'done',
            'priority' => 'high',
            'due_date' => '2026-04-10',
            'project_id' => $project2->project_id,
        ]);

        Task::create([
            'name' => 'Запустить таргетинг',
            'description' => 'Настроить рекламные кампании в VK и Telegram',
            'status' => 'in_progress',
            'priority' => 'medium',
            'due_date' => '2026-04-28',
            'project_id' => $project2->project_id,
        ]);

        Task::create([
            'name' => 'Собрать требования',
            'description' => 'Провести интервью с потенциальными пользователями',
            'status' => 'done',
            'priority' => 'medium',
            'due_date' => '2026-04-05',
            'project_id' => $project3->project_id,
        ]);

        Task::create([
            'name' => 'Проанализировать конкурентов',
            'description' => 'Изучить Trello, Jiram Asana и их ИИ-функции',
            'status' => 'todo',
            'priority' => 'low',
            'due_date' => '2026-05-20',
            'project_id' => $project3->project_id,
        ]);

        // Назначение исполнителей
        $ivan = User::find(1);
        $maria = User::find(2);
        $alexey = User::find(3);

        // Проект 1: Иван и Мария
        $project1 = Project::find(1);
        $project1->users()->attach([$ivan->id, $maria->id], ['role_in_project' => 'member']);

        // Проект 2: Мария и Алексей
        $project2 = Project::find(2);
        $project2->users()->attach([$maria->id, $alexey->id], ['role_in_project' => 'member']);

        // Проект 3: Иван и Алексей
        $project3 = Project::find(3);
        $project3->users()->attach([$ivan->id, $alexey->id], ['role_in_project' => 'member']);

        // Назначение исполнителей задач
        $task1 = Task::find(1); // Спроектировать БД
        $task1->users()->attach($ivan->id, ['assignment_date' => now()]);

        $task2 = Task::find(2); // Аунтефикация
        $task2->users()->attach($maria->id, ['assignment_date' => now()]);

        $task3 = Task::find(3); // ИИ-ассистент
        $task3->users()->attach($ivan->id, ['assignment_date' => now()]);

        $task4 = Task::find(4); // Контент-план
        $task4->users()->attach($maria->id, ['assignment_date' => now()]);

        $task5 = Task::find(5); // Таргетинг
        $task5->users()->attach($alexey->id, ['assignment_date' => now()]);

        $task6 = Task::find(6); // Собрать требования
        $task6->users()->attach($ivan->id, ['assignment_date' => now()]);

        $task7 = Task::find(7); // Анализ конкурентов
        $task7->users()->attach($alexey->id, ['assignment_date' => now()]);
    }

}
