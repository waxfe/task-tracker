<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProjectsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Project::create([
            'name' => 'Разработка веб-приложения',
            'description' => 'Проект по созданию системы трекинга задач с ИИ-ассистентом',
        ]);

        Project::create([
            'name' => 'Маркетинговая компания',
            'description' => 'Запуск рекламной кампании в социальных сетях',
        ]);

        Project::create([
            'name' => 'Исследование рынка',
            'description' => 'Анализ конкурентов и сбор требований',
        ]);
    }
}
