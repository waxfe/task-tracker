<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\AiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Http;

class AiServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест 1: AI-сервис успешно возвращает рекомендации
     */
    public function test_ai_service_returns_recommendations_on_successful_api_call()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id, ['role_in_project' => 'member']);

        $task = Task::factory()->create([
            'project_id' => $project->project_id,
            'name' => 'Тестовая задача',
            'status' => 'todo',
        ]);
        $task->users()->attach($user->id);

        // Подмена HTTP-клиента для имитации успешного ответа
        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => '["Рекомендация 1"], ["Рекомендация 2"]']]
                ]
            ], 200)
        ]);

        // Отправка запроса на AI-анализ задачи
        $response = $this->actingAs($user)->postJson("/tasks/{$task->task_id}/ai-analyze");

        // Проверка успешного ответа
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure(['analysis']);

        // Проверка что запись с AI сохранилась в БД
        $this->assertDatabaseHas('ai_interactions', [
            'task_id' => $task->task_id,
            'user_id' => $user->id,
            'request_type' => 'task_analysis',
        ]);
    }

    /**
     * Тест 2: При недоступности API срабатывает fallback-механизм
     */
    public function test_ai_chat_uses_fallback_when_api_fails()
    {
        $user = User::factory()->create();

        // Имитация ошибки API
        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response(null, 503)
        ]);

        $response = $this->actingAs($user)->postJson('/ai-chat', [
            'message' => 'Какие риски в проекте?',
            'context_type' => 'general',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $reply = $response->json('reply');
        $this->assertNotEmpty($reply);
        $this->assertIsString($reply);
    }

    /**
     * Тест 3: Повторный запрос к AI использует кэш, а не обращается к API
     */
    public function test_ai_service_caches_repeated_requests()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id, ['role_in_project' => 'member']);

        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => '["Рекомендация 1", "Рекомендация 2"]']]
                ]
            ], 200)
        ]);

        // Первый запрос должен вызвать API
        $response1 = $this->actingAs($user)->postJson("/projects/{$project->project_id}/ai-analyze");
        $response1->assertStatus(200);

        $this->assertDatabaseHas('ai_interactions', [
            'project_id' => $project->project_id,
            'user_id' => $user->id,
            'request_type' => 'project_analysis',
        ]);

        // Второй запрос должен взять ответ из кэша
        Http::fake();
        Http::preventingStrayRequests();

        $response2 = $this->actingAs($user)->postJson("/projects/{$project->project_id}/ai-analyze");
        $response2->assertStatus(200);

        // Проверка что оба ответа одинаковы
        $this->assertEquals($response1->json('analysis'), $response2->json('analysis'));
    }
}
