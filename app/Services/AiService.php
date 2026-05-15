<?php

namespace App\Services;

use App\Models\Project;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Листинг сервисного слоя
class AiService
{
    // Основной метод для работы чата с ИИ
    public function chat($message, $context = null, $temperature = 0.7, $maxTokens = 500)
    {
        // Формирование массива сообщений для ИИ
        $messages = $this->buildMessage($message, $context);

        // Отправка запроса к внешнему API
        $reply = $this->callApi($messages, $temperature, $maxTokens);

        // Если API недоступен — используется fallback-ответ
        return $reply ?: $this->mockResponse($message, $context);
    }

    // Отправка HTTP-запроса к ИИ-сервису
    private function callApi($messages, $temperature = 0.7, $maxTokens = 500)
    {
        $apiKey = env('OPENROUTER_API_KEY');

        // Проверка наличия API-ключа
        if (!$apiKey) {
            Log::warning('OpenRouter API key is not found');
            return null;
        }

        try {
            // Выполнение POST-запроса к OpenRouter API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://openrouter.ai/api/v1/chat/completions', [
                        'model' => 'google/gemini-2.5-flash-lite',
                        'messages' => $messages,
                        'temperature' => $temperature,
                        'max_tokens' => $maxTokens,
                    ]);

            // Проверка успешности ответа
            if ($response->failed()) {
                Log::error('OpenRouter error' . $response->body());
                return null;
            }

            // Получение текста ответа модели
            $reply = $response->json()['choices'][0]['message']['content'] ?? null;

            return $reply;
        } catch (Exception $e) {

            // Обработка ошибок соединения с API
            Log::error('OpnRouter exception:' . $e->getMessage());

            return null;
        }
    }

    // Формирование структуры сообщений для ИИ
    private function buildMessage($message, $context)
    {
        // Формирование системного промпта
        $systemPromt = $this->buildSystemPromt($context);

        return [
            ['role' => 'system', 'content' => $systemPromt],
            ['role' => 'user', 'content' => $message],
        ];
    }

    // Формирование системного контекста для ИИ
    private function buildSystemPromt($context)
    {
        $prompt = "Ты — AI-ассистент системы управления проектами TaskAssist. ";

        // Если запрос связан с проектом — добавляется контекст проекта
        if ($context && $context['type'] === 'project' && $context['tasks']) {

            $prompt .= "Задачи в проекте:\n";

            // Добавление информации о задачах проекта
            foreach ($context['tasks'] as $task) {

                $dueDate = $task['due_date'] ?? 'не указано';

                $prompt .= "- {$task['name']} (описание задачи: {$task['description']}, статус: {$task['status']},
                    приоритет: {$task['priority']}, срок выполнения: {$dueDate})\n";
            }

            // Получение информации об участниках проекта
            $project = Project::find($context['id']);

            if ($project) {

                $members = $project->users->map(fn($u) => [
                    'name' => $u->name,
                    'tasks_count' => $u->tasks()
                        ->where('project_id', $project->project_id)
                        ->count(),
                ])->toArray();

                // Добавление информации о загрузке участников
                foreach ($members as $member) {

                    $prompt .= "- {$member['name']}: {$member['tasks_count']} задач\n";
                }
            }

        } else {

            // Общий режим работы ИИ без контекста проекта
            $prompt .= "Отвечай на общие вопросы по управлению проектами, задачами и продуктивности. ";
        }

        // Инструкции по формату ответа модели
        $prompt .= "Отвечай кратко, по делу, на русском языке";
        $prompt .= "Используй эмодзи для наглядности";
        $prompt .= "Если не знаешь ответа - скажи честно";
        $prompt .= "Не придумывай лишнего";
        $prompt .= "Форматируй ответ: используй **жирный текст** для заголовков, 
        *курсив* для акцентов, списки — через * или -. Не используй Markdown-заголовки #. ";

        return $prompt;
    }

    // Метод для анализа проекта или задачи
    public function analyze($systemPrompt, $userMessage = null, $temperature = 0.3, $maxTokens = 300)
    {
        // Базовое системное сообщение
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        // Добавление пользовательского запроса
        if ($userMessage) {
            $messages[] = ['role' => 'user', 'content' => $userMessage];
        }

        return $this->callApi($messages, $temperature, $maxTokens);
    }

    // Резервные mock-ответы при недоступности AI API
    private function mockResponse($message, $context)
    {
        $lowerMsg = strtolower($message);

        // Ответ при запросах о рисках и сроках
        if (str_contains($lowerMsg, 'риск') || str_contains($lowerMsg, 'срок')) {

            return "⚠️ В проекте обнаружены риски нарушения сроков. 
            Рекомендуется пересмотреть приоритет задач и провести ежедневные митинги для контроля прогресса.";
        }

        // Ответ при запросах о декомпозиции задач
        if (str_contains($lowerMsg, 'декомпозиция') || str_contains($lowerMsg, 'разбить')) {

            return "📋 Рекомендуемая декомпозиция задачи:\n1. Анализ требований\n2. 
             Проектирование решения\n3. Разработка\n4. Тестирование\n5. Внедрение";
        }

        // Mock-анализ проекта
        if ($context && $context['type'] === 'project') {

            return "🔍 Анализ проекта «{$context['name']}»:\n\n- Всего задач: 12\n- Выполнено: 5\n- 
            В работе: 4\n- Просрочено: 1\n\nРекомендация: обратить внимание на просроченные задачи и перераспределить нагрузку.";
        }

        // Ответ по умолчанию
        return "🤖 Я AI-ассистент. Ваш вопрос: «{$message}». Для более точного ответа уточните контекст или задайте конкретный вопрос о проекте/задаче.";
    }
}