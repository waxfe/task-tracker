<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class HistoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест 1: При изменении названия задачи создаётся запись в истории
     */
    public function test_history_is_created_when_task_name_is_changed()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id, ['role_in_project' => 'owner']);

        $task = Task::factory()->create([
            'project_id' => $project->project_id,
            'name' => 'Старое название',
        ]);

        $response = $this->actingAs($user)->putJson("/tasks/{$task->task_id}", [
            'name' => 'Новое название',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('task_histories', [
            'task_id' => $task->task_id,
            'user_id' => $user->id,
            'changed_field' => 'name',
            'old_value' => 'Старое название',
            'new_value' => 'Новое название',
        ]);
    }

    /**
     * Тест 2: При изменении статуса задачи создаётся запись в истории
     */
    public function test_history_is_created_when_task_status_is_changed()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id, ['role_in_project' => 'owner']);

        $task = Task::factory()->create([
            'project_id' => $project->project_id,
            'status' => 'todo',
        ]);

        $response = $this->actingAs($user)->putJson("/tasks/{$task->task_id}", [
            'status' => 'in_progress',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('task_histories', [
            'task_id' => $task->task_id,
            'user_id' => $user->id,
            'changed_field' => 'status',
            'old_value' => 'todo',
            'new_value' => 'in_progress',
        ]);
    }
}
