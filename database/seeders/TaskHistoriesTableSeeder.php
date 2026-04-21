<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\TaskHistory;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TaskHistoriesTableSeeder extends Seeder
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

        TaskHistory::create([
            'changed_field' => 'priority',
            'old_value' => 'high',
            'new_value' => 'medium',
            'task_id' => $task1->task_id,
            'user_id' => $ivan->id,
        ]);

        TaskHistory::create([
            'changed_field' => 'status',
            'old_value' => 'done',
            'new_value' => 'in_progress',
            'task_id' => $task1->task_id,
            'user_id' => $maria->id,
        ]);

        TaskHistory::create([
            'changed_field' => 'name',
            'old_value' => 'Реализовать аунтефикацию',
            'new_value' => 'Сделать аунтефикацию',
            'task_id' => $task2->task_id,
            'user_id' => $ivan->id,
        ]);
    }
}
