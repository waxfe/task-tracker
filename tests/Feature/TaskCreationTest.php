<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskCreationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест 1: успешное создание задачи
     */
    public function test_user_can_create_task_in_their_project()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id, ['role_in_project' => 'member']);

        $response = $this->actingAs($user)->postJson('/tasks', [
            'name' => 'Новая задача',
            'project_id' => $project->project_id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('tasks', ['name' => 'Новая задача']);
    }

    /**
     * Тест 2: Название задачи обязательно
     */
    public function test_task_name_is_required()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id, ['role_in_project' => 'member']);

        $response = $this->actingAs($user)->postJson('/tasks', [
            'project_id' => $project->project_id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    /**
     * Тест 3: По умолчанию исполнитель — текущий пользователь
     */
    public function test_current_user_is_default_executor()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id, ['role_in_project' => 'member']);

        $response = $this->actingAs($user)->postJson('/tasks', [
            'name' => 'Моя задача',
            'project_id' => $project->project_id,
        ]);

        $response->assertStatus(201);

        $task = Task::where('name', 'Моя задача')->first();
        $this->assertTrue($task->users->contains($user));
    }
}