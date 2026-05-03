<?php

namespace App\Http\Controllers;

use App\Models\AiInteraction;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskHistory;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    protected $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function show(Task $task)
    {
        $user = Auth::user();

        $isProjectmember = $task->project->users()->where('user_id', $user->id)->exists();
        $isExecutor = $task->users()->where('user_id', $user->id)->exists();

        if (!$isProjectmember && !$isExecutor) {
            return response()->json([
                'success' => false,
                'message' => 'Нет доступа к задаче',
            ], 403);
        }

        // AI-рекомендации
        $lastAnalys = AiInteraction::where('task_id', $task->task_id)
            ->where('request_type', 'task_analysis')
            ->latest()
            ->first();

        $aiRecommendations = $lastAnalys ? json_decode($lastAnalys->output_data, true) : [];


        $data = [
            'id' => $task->task_id,
            'name' => $task->name,
            'description' => $task->description,
            'status' => $task->status,
            'priority' => $task->priority,
            'due_date' => $task->due_date?->format('Y-m-d'),
            // Исполнители
            'user_ids' => $task->users()->pluck('user_id'),

            // Все пользователи проекта
            'available_users' => $task->project->users->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
            ]),

            // Комментарии
            'comments' => $task->comments->map(fn($c) => [
                'id' => $c->comment_id,
                'text' => $c->text,
                'user' => [
                    'id' => $c->user->id,
                    'name' => $c->user->name,
                ],
                'created_at' => $c->created_at->format('d.m.Y H:i'),
            ]),

            'ai_recommendations' => $aiRecommendations,

            // История изменений
            'history' => $task->histories->map(fn($h) => [
                'id' => $h->history_id,
                'changed_field' => $h->changed_field,
                'old_value' => $h->old_value,
                'new_value' => $h->new_value,
                'user' => [
                    'id' => $h->user->id,
                    'name' => $h->user->name,
                ],
                'change_date' => $h->change_date->format('d.m.Y H:i'),
            ]),

            'created_at' => $task->created_at?->format('d.m.Y H:i'),
            'updated_at' => $task->updated_at?->format('d.m.Y H:i'),
        ];

        return response()->json($data);
    }

    public function store(Request $request)
    {

        $user = Auth::user();
        $project = Project::find($request->project_id);

        if (!$project || !$project->users()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Вы не являетесь участником проекта',
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'project_id' => 'required',
            'description' => 'nullable|string',
            'status' => 'nullable|in:todo,in_progress,done',
            'priority' => 'nullable|in:high,medium,low',
            'due_date' => 'nullable|date',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $task = Task::create([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status ?? 'todo',
            'priority' => $request->priority ?? 'medium',
            'due_date' => $request->due_date,
            'project_id' => $request->project_id,
        ]);

        $finalUserIds = $request->user_ids ?? [];
        $currentUserId = Auth::id();

        if (empty($finalUserIds)) {
            $finalUserIds[] = $currentUserId;
        }

        $task->users()->sync($finalUserIds);

        return response()->json([
            'success' => true,
            'task_id' => $task->task_id,
            'message' => 'Задача создана',
        ], 201);
    }

    public function update(Request $request, $id)
    {
        // Получить задачу
        $task = Task::findOrFail($id);
        $user = Auth::user();
        $project = $task->project;

        // Проверка что пользователь является исполнителем или участником
        $member = $project->users()->where('user_id', $user->id)
            ->select('users.*', 'project_user.role_in_project')
            ->first();
        $isProjectmember = (bool) $member;
        $isOwner = $isProjectmember && $member->pivot?->role_in_project === 'owner';
        $isExecutor = $task->users()->where('user_id', $user->id)->exists();

        if (!$isProjectmember && !$isExecutor) {
            return response()->json([
                'success' => false,
                'message' => 'Нет прав для редактирования',
            ], 403);
        }

        if (!$isOwner && $request->has('user_ids')) {
            return response()->json([
                'success' => false,
                'message' => 'Только владелец может менять исполнителей'
            ], 403);
        }

        if (
            !$isOwner && !$isExecutor && ($request->has('description') || $request->has('status')
                || $request->has('priority') || $request->has('due_date'))
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Только исполнитель или владелец могут менять поля задачи'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:todo,in_progress,done',
            'priority' => 'nullable|in:high,medium,low',
            'due_date' => 'nullable|date',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        // Изменения в истории
        $old = $task->getOriginal();
        $new = $request->only(['name', 'status', 'priority', 'due_date', 'description']);

        foreach ($new as $field => $value) {

            $oldValue = $old[$field] ?? '';
            $newValue = $value ?? '';
            if ($oldValue != $newValue) {
                TaskHistory::create([
                    'task_id' => $task->task_id,
                    'user_id' => $user->id,
                    'changed_field' => $field,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                ]);
            }
        }

        // Сравнение исполнителей
        $oldExecutors = $task->users()->pluck('id')->toArray();
        $newExecutors = $request->user_ids ?? [];

        if ($request->has('user_ids') && $oldExecutors != $newExecutors) {
            TaskHistory::create([
                'task_id' => $task->task_id,
                'user_id' => $user->id,
                'changed_field' => 'user_ids',
                'old_value' => implode(',', $oldExecutors),
                'new_value' => implode(',', $newExecutors),
            ]);
        }

        $task->update($validated);

        if ($isOwner && isset($validated['user_ids'])) {
            $task->users()->sync($validated['user_ids']);
        }

        $task->load(['histories.user', 'users', 'project.users']);

        return response()->json([
            'success' => true,
            'message' => 'Задача обновлена',
            'task' => [
                'id' => $task->task_id,
                'name' => $task->name,
                'description' => $task->description,
                'status' => $task->status,
                'priority' => $task->priority,
                'due_date' => $task->due_date?->format('d.m.Y'),
                'user_ids' => $task->users->pluck('id'),
                'available_users' => $task->project->users->map(fn($u) => ['id' => $u->id, 'name' => $u->name]),
                'history' => $task->histories->map(function ($history) {
                    return [
                        'id' => $history->history_id,
                        'changed_field' => $history->changed_field,
                        'old_value' => $history->old_value,
                        'new_value' => $history->new_value,
                        'change_date' => $history->change_date->format('d.m.Y H:i') ?? date('d.m.Y H:i'),
                        'user' => [
                            'id' => $history->user->id,
                            'name' => $history->user->name,
                        ],
                    ];
                }),
            ]
        ]);
    }

    public function destroy($id)
    {
        $task = Task::findOrFail($id);
        $user = Auth::user();
        $project = $task->project;

        $isOwner = $project->users()->where('user_id', $user->id)->first()->pivot->role_in_project === 'owner';

        if (!$isOwner) {
            return response()->json([
                'success' => false,
                'message' => 'Нет прав для удаления',
            ], 403);
        }

        // Удалить связанные данные
        $task->aiInteractions()->delete();
        $task->comments()->delete();
        $task->histories()->delete();

        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Задача удалена',
        ]);
    }

    public function aiAnalyze(Task $task)
    {
        $user = Auth::user();
        $project = $task->project;

        $member = $project->users()->where('user_id', $user->id)->exists();
        $executorsNames = implode(', ', $task->users->pluck('name')->toArray());

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Нет доступа',
            ], 403);
        }

        $stats = [
            'name' => $task->name,
            'description' => $task->description,
            'status' => $task->status,
            'priority' => $task->priority,
            'due_date' => $task->due_date?->format('d.m.Y'),
            'created_at' => $task->created_at?->format('d.m.Y'),
            'executors' => $executorsNames,
        ];

        $prompt = "Ты — аналитик задачи. Проанализируй задачу '{$task->name}'.\n\n";
        $prompt .= "Параметры задачи:\n";
        $prompt .= "- Описание: {$stats['description']}\n";
        $prompt .= "- Статус: {$stats['status']}\n";
        $prompt .= "- Приоритет: {$stats['priority']}\n";
        $prompt .= "- Срок выполнения: {$stats['due_date']}\n";
        $prompt .= "- Дата создания: {$stats['created_at']}\n";
        $prompt .= "- Исполнители: {$stats['executors']}\n";

        $prompt .= "Дай 2-3 короткие рекомендации. Каждая рекомендация — одно-два предложение.
        Используй эмодзи в начале (⚠️, 📊, 🔥, ✅, 🚨, 🎯). 
        ВАЖНО: не используй кавычки внутри текста рекомендаций.
        Формат ответа: только массив JSON, без пояснений, без markdown. 
        Пример: [\"⚠️ Задача выглядит перегруженной. Разбейте на подзадачи\", \"📊 ⚠️ Возможен риск срыва срока выполнения. Рекомендуется уточнить требования. \"]";

        $reply = $this->aiService->analyze($prompt);

        $reply = preg_replace('/```json\s*/i', '', $reply);
        $reply = preg_replace('/```\s*/i', '', $reply);
        $reply = trim($reply);

        $reccomendations = json_decode($reply, true);

        if (is_array($reccomendations)) {
            $output = array_map(function ($r) {
                $r = trim($r, '"\'');
                $r = str_replace('\\"', '', $r);
                $r = str_replace('"', '', $r);
                return trim($r);
            }, $reccomendations);
            $outputForDB = json_encode($output);
        } else {
            $output = explode("\n", trim($reply));
            $output = array_values(array_filter($output, fn($line) => strlen($line) > 10));
            $output = array_map(function ($r) {
                $r = trim($r, '"\'- ');
                $r = str_replace('\\"', '', $r);
                $r = str_replace('"', '', $r);
                return trim($r);
            }, $output);
            $outputForDB = json_encode($output);
        }

        AiInteraction::create([
            'user_id' => $user->id,
            'task_id' => $task->task_id,
            'request_type' => 'task_analysis',
            'input_data' => json_encode(['stats' => $stats]),
            'output_data' => $outputForDB,
            'request_date' => now(),
        ]);

        return response()->json([
            'success' => true,
            'analysis' => $output,
        ]);
    }
}
