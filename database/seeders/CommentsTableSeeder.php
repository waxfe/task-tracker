<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CommentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ivan = User::find(1);
        $maria = User::find(2);
        $task1 = Task::find(1);
        $task2 = Task::find(2);

        Comment::create([
            'text' => 'Схема БД готова, можно приступать к миграциям',
            'task_id' => $task1->task_id,
            'user_id' => $ivan->id,
        ]);

        Comment::create([
            'text' => 'Проверьте пж внешние ключи',
            'task_id' => $task1->task_id,
            'user_id' => $maria->id,
        ]);

        Comment::create([
            'text' => 'Breeze установил, сейчас настраиваю страницы',
            'task_id' => $task2->task_id,
            'user_id' => $ivan->id,
        ]);


    }
}
