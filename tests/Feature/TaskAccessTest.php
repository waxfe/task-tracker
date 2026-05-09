<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TaskAccessTest extends TestCase
{
    use RefreshDatabase;

    /** 
     * Тест 1: Владелец проекта может редактировать задачу
     */
    public function test_owner_can_update_task()
    {
        $owner = User::factory()->create();
        $executor = User::factory()->create();

        $project = Project::factory()->create();
        $project->users()->attach($owner->id, ['role_in_project' => 'owner']);
        $project->users()->attach($executor->id, ['role_in_project' => 'member']);

        $task = Task::factory()->create(['project_id' => $project->project_id]);
        $task->users()->attach($executor->id);

        $response = $this->actingAs($owner)->putJson("/tasks/{$task->task_id}", [
            'name' => 'Новое название от владельца',
            'status' => 'done',
            'user_ids' => [$owner->id, $executor->id],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', ['name' => 'Новое название от владельца']);
    }

    /**
     * Тест 2: Владелец проекта может удалить задачу
     */
    public function test_owner_can_delete_task()
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($owner->id, ['role_in_project' => 'owner']);
        $task = Task::factory()->create(['project_id' => $project->project_id]);

        $response = $this->actingAs($owner)->deleteJson("/tasks/{$task->task_id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tasks', ['task_id' => $task->task_id]);
    }

    /**
     * Тест 3: Исполнитель может редактировать поля задачи, но НЕ исполнителей
     */
    public function test_executor_can_update_task_fields_but_not_assignees()
    {
        $owner = User::factory()->create();
        $executor = User::factory()->create();
        $otherUser = User::factory()->create();

        $project = Project::factory()->create();
        $project->users()->attach($owner->id, ['role_in_project' => 'owner']);
        $project->users()->attach($executor->id, ['role_in_project' => 'member']);
        $project->users()->attach($otherUser->id, ['role_in_project' => 'member']);

        $task = Task::factory()->create([
            'project_id' => $project->project_id,
            'status' => 'todo',
            'priority' => 'medium',
        ]);
        $task->users()->attach($executor->id);

        // ACT 1: исполнитель меняет статус и приоритет
        $response = $this->actingAs($executor)->putJson("/tasks/{$task->task_id}", [
            'status' => 'in_progress',
            'priority' => 'high',
        ]);

        // ASSERT 1
        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', [
            'task_id' => $task->task_id,
            'status' => 'in_progress',
            'priority' => 'high',
        ]);

        // ACT 2: исполнитель пытается сменить исполнителей
        $response = $this->actingAs($executor)->putJson("/tasks/{$task->task_id}", [
            'user_ids' => [$otherUser->id]
        ]);

        // ASSERT 2
        $response->assertStatus(403);
        $response->assertJson(['message' => 'Только владелец может менять исполнителей']);
    }

    /**
     * Тест 4: Участник проекта (не исполнитель) НЕ может редактировать задачу
     */
    public function test_project_member_cannot_edit_task()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $project = Project::factory()->create();
        $project->users()->attach($owner->id, ['role_in_project' => 'owner']);
        $project->users()->attach($member->id, ['role_in_project' => 'member']);

        $task = Task::factory()->create(['project_id' => $project->project_id]);

        // Попытка отредактировать задачу
        $response = $this->actingAs($member)->putJson("/tasks/{$task->task_id}", [
            'status' => 'done',
        ]);

        $response->assertStatus(403);
        $response->assertJson(['message' => 'Только исполнитель или владелец могут менять поля задачи']);
    }
}
