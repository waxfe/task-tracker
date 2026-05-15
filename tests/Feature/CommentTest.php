<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

// Листинг теста обработки комментариев
class CommentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест 1: Участник проекта может добавить комментарий к задаче
     */
    public function test_project_member_can_add_comment_to_task()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id, ['role_in_project' => 'member']);

        $task = Task::factory()->create(['project_id' => $project->project_id]);

        $response = $this->actingAs($user)->postJson("/tasks/{$task->task_id}/comments", [
            'text' => 'Тестовый комментарий',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('comments', [
            'text' => 'Тестовый комментарий',
            'user_id' => $user->id,
            'task_id' => $task->task_id,
        ]);
    }

    /**
     * Тест 2: Участник проекта не может изменить чужой комментарий
     */
    public function test_project_member_cannot_update_other_comment()
    {
        $author = User::factory()->create();
        $anotherMember = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($author->id, ['role_in_project' => 'member']);
        $project->users()->attach($anotherMember->id, ['role_in_project' => 'member']);

        $task = Task::factory()->create(['project_id' => $project->project_id]);

        $comment = $task->comments()->create([
            'text' => 'Чужой комментарий',
            'user_id' => $author->id,
        ]);

        $response = $this->actingAs($anotherMember)->putJson("/tasks/{$task->task_id}/comments/{$comment->comment_id}", [
            'text' => 'Не чужой комментарий'
        ]);

        $response->assertStatus(403);
        $response->assertJson(['success' => false]);
    }

    /**
     * Тест 3: автор комментария может удалить свой комментарий 
     */
    public function test_author_can_delete_own_comment()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id, ['role_in_project' => 'member']);

        $task = Task::factory()->create(['project_id' => $project->project_id]);

        $comment = $task->comments()->create([
            'text' => 'Комментарий для удаления',
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->deleteJson("/tasks/{$task->task_id}/comments/{$comment->comment_id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('comments', ['comment_id' => $comment->comment_id]);
    }
}
