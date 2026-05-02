<?php

namespace App\Http\Controllers;

use App\Models\AiInteraction;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Services\AiService;
use Carbon\Carbon;

class AiChatController extends Controller
{

    protected $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function index()
    {
        $user = Auth::user();

        // Получить все проекты пользователя
        $projects = $user->projects;

        // Получить последние 50 сообщений чата
        $messages = AiInteraction::where('user_id', $user->id)
            ->where('request_type', 'chat')
            ->orderBy('created_at', 'asc')
            ->get()
            ->flatMap(function ($item) {
                return [
                    (object) [
                        'is_user' => true,
                        'message' => $item->input_data,
                        'created_at' => Carbon::parse($item->request_date),
                        'suggestions' => null,
                    ],
                    (object) [
                        'is_user' => false,
                        'message' => $item->output_data,
                        'created_at' => Carbon::parse($item->request_date),
                        'suggestions' => null,
                    ],
                ];
            });

        $selectedProjectId = $projects->first()?->project_id;

        return view('ai-chat.index', compact('projects', 'selectedProjectId', 'messages'));
    }

    public function clearHistory()
    {
        $user = Auth::user();
        AiInteraction::where('user_id', $user->id)
            ->where('request_type', 'chat')
            ->delete();

        return response()->json(['success' => true]);
    }

    public function sendMessage(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'message' => 'required|string|max:2000',
            'context_type' => 'required|in:general,project,task',
            'context_id' => 'nullable|integer',
        ]);

        $context = $this->buildContext($request);

        // Кэширование ответа на 1 час
        $cacheKey = 'ai_chat_' . md5($request->message . $request->context_type . $request->context_id);

        $reply = Cache::remember($cacheKey, 3600, function () use ($request, $context) {
            return $this->aiService->chat($request->message, $context);
        });

        if (!$reply) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка подключения к AI-сервису.'
            ], 500);
        }

        // Сохранить запрос пользователя
        AiInteraction::create([
            'user_id' => $user->id,
            'project_id' => $context['type'] === 'project' ? $context['id'] : null,
            'task_id' => $context['type'] === 'task' ? $context['id'] : null,
            'request_type' => 'chat',
            'input_data' => $request->message,
            'output_data' => $reply,
            'request_date' => now(),
        ]);

        // Генерация примеров запросов
        $suggestions = $this->getSuggestions($context);

        return response()->json([
            'success' => true,
            'reply' => $reply,
            'suggestions' => $suggestions,
        ]);
    }

    // Формирование контекста для AI на основе выбранного типа и ID
    private function buildContext(Request $request)
    {
        $context = [
            'id' => $request->context_id,
            'type' => $request->context_type,
            'name' => null,
            'description' => null,
            'status' => null,
            'priority' => null,
            'due_date' => null,
        ];

        if ($request->context_type === 'project' && $request->context_id) {
            $project = Project::find($request->context_id);
            if ($project) {
                $context['name'] = $project->name;
                $context['description'] = $project->description;
                $context['tasks'] = $project->tasks->map(fn($t) => [
                    'name' => $t->name,
                    'description' => $t->description,
                    'status' => $t->status,
                    'priority' => $t->priority,
                    'due_date' => $t->due_date?->format('d.m.Y'),
                ])->toArray();
            }
        }

        return $context;
    }

    // Статические примеры запросов
    private function getSuggestions($context)
    {
        if ($context['type'] === 'project') {
            return [
                'Какие задачи требуют внимания?',
                'Почему снижается темп выполнения',
                'Есть ли риски срыва сроков?',
                'Как лучше распределить задачи?',
            ];
        } elseif ($context['type'] === 'task') {
            return [
                'Как разюить задачу на подзадачи?',
                'Оцени сложность выполнения',
                'Какие риски у этой задачи?',
                'Рекомендации по приоритету',
            ];
        }

        return [
            'Как эффективно управлять проектами?',
            'Советы по декомпозиции задач',
            'Как оценить сроки выполнения?',
        ];
    }
}
