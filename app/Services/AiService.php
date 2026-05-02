<?php

namespace App\Services;

use App\Models\Project;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    public function chat($message, $context = null)
    {
        $messages = $this->buildMessage($message, $context);
        $reply = $this->callApi($messages);

        return $reply ?: $this->mockResponse($message, $context);
    }

    private function callApi($messages)
    {
        $apiKey = env('OPENROUTER_API_KEY');

        if (!$apiKey) {
            Log::warning('OpenRouter API key is not found');
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://openrouter.ai/api/v1/chat/completions', [
                        'model' => 'google/gemini-2.5-flash-lite',
                        'messages' => $messages,
                        'temperature' => 0.7,
                        'max_tokens' => 500,
                    ]);

            if ($response->failed()) {
                Log::error('OpenRouter error' . $response->body());
                return null;
            }

            $reply = $response->json()['choices'][0]['message']['content'] ?? null;

            return $reply;
        } catch (Exception $e) {
            Log::error('OpnRouter exception:' . $e->getMessage());
            return null;
        }
    }

    private function buildMessage($message, $context)
    {
        $systemPromt = $this->buildSystemPromt($context);

        return [
            ['role' => 'system', 'content' => $systemPromt],
            ['role' => 'user', 'content' => $message],
        ];
    }

    private function buildSystemPromt($context)
    {
        $prompt = "Ты — AI-ассистент системы управления проектами TaskAssist. ";

        if ($context && $context['type'] === 'project' && $context['tasks']) {
            $prompt .= "Задачи в проекте:\n";
            foreach ($context['tasks'] as $task) {
                $dueDate = $task['due_date'] ?? 'не указано';
                $prompt .= "- {$task['name']} (описание задачи: {$task['description']}, статус: {$task['status']},
                    приоритет: {$task['priority']}, срок выполнения: {$dueDate})\n";
            }
            $project = Project::find($context['id']);
            if ($project) {
                $members = $project->users->map(fn($u) => [
                    'name' => $u->name,
                    'tasks_count' => $u->tasks()->where('project_id', $project->project_id)->count(),
                ])->toArray();
                foreach ($members as $member) {
                    $prompt .= "- {$member['name']}: {$member['tasks_count']} задач\n";
                }
            }
        } else {
            $prompt .= "Отвечай на общие вопросы по управлению проектами, задачами, Agile и продуктивности. ";
        }

        $prompt .= "Отвечай кратко, по делу, на русском языке";
        $prompt .= "Используй эмодзи для наглядности";
        $prompt .= "Если не знаешь ответа - скажи честно";
        $prompt .= "Не придумывай лишнего";
        $prompt .= "Форматируй ответ: используй **жирный текст** для заголовков, 
        *курсив* для акцентов, списки — через * или -. Не используй Markdown-заголовки #. ";

        return $prompt;
    }

    private function mockResponse($message, $context)
    {
        $lowerMsg = strtolower($message);

        if (str_contains($lowerMsg, 'риск') || str_contains($lowerMsg, 'срок')) {
            return "⚠️ В проекте обнаружены риски нарушения сроков. 
            Рекомендуется пересмотреть приоритет задач и провести ежедневные митинги для контроля прогресса.";
        }

        if (str_contains($lowerMsg, 'декомпозиция') || str_contains($lowerMsg, 'разбить')) {
            return "📋 Рекомендуемая декомпозиция задачи:\n1. Анализ требований\n2. 
             Проектирование решения\n3. Разработка\n4. Тестирование\n5. Внедрение";
        }

        if ($context && $context['type'] === 'project') {
            return "🔍 Анализ проекта «{$context['name']}»:\n\n- Всего задач: 12\n- Выполнено: 5\n- 
            В работе: 4\n- Просрочено: 1\n\nРекомендация: обратить внимание на просроченные задачи и перераспределить нагрузку.";
        }

        return "🤖 Я AI-ассистент. Ваш вопрос: «{$message}». Для более точного ответа уточните контекст или задайте конкретный вопрос о проекте/задаче.";
    }
}