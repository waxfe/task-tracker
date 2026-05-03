<?php

namespace App\Http\Controllers;

use App\Models\AiInteraction;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskHistory;
use App\Models\User;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    protected $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function show(Project $project)
    {
        $user = Auth::user();

        if (!$project->users->contains($user)) {
            abort(403, 'У вас нет доступа к этому проекту.');
        }

        $projects = $user->projects;
        $selectedProject = $project;

        // Получить задачи
        $tasks = $project->tasks()->with('users', 'aiInteractions')->get();

        $activeTasksCount = $project->tasks()
            ->whereIn('status', ['todo', 'in_progress'])
            ->whereHas('users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->count();

        $stats = [
            'total' => $tasks->count(),
            'todo' => $tasks->where('status', 'todo')->count(),
            'in_progress' => $tasks->where('status', 'in_progress')->count(),
            'done' => $tasks->where('status', 'done')->count(),
            'overdue' => $tasks->filter(function ($task) {  // filter работает на коллекции
                return $task->due_date && $task->due_date < now() && $task->status !== 'done';
            })->count(),
            'high_priority' => $tasks->where('priority', 'high')->count(),
            'medium_priority' => $tasks->where('priority', 'medium')->count(),
            'low_priority' => $tasks->where('priority', 'low')->count(),
        ];

        $stats['progress'] = $stats['total'] > 0
            ? round(($stats['done'] / $stats['total']) * 100) : 0;

        $members = $project->users;
        $currentUser = $user;
        $isOwner = $project->users()->where('user_id', $user->id)->first()->pivot->role_in_project === 'owner';

        $lastAnalysis = AiInteraction::where('project_id', $project->project_id)
            ->where('request_type', 'project_analysis')
            ->latest()
            ->first();

        $lastAnalysisOutput = $lastAnalysis ? json_decode($lastAnalysis->output_data, true) : [];

        return view('projects.show', compact('project', 'projects', 'selectedProject', 'tasks', 'stats', 'members', 'currentUser', 'isOwner', 'lastAnalysisOutput', 'activeTasksCount'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $user = Auth::user();

        $project = Project::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        // Добавление пользователя как владельца проекта
        $project->users()->attach($user->id, ['role_in_project' => 'owner']);

        return redirect()->route('dashboard', ['project_id' => $project->project_id])
            ->with('success', 'Проект успешно создан');
    }

    public function update(Request $request, Project $project)
    {
        $user = Auth::user();

        // Проверяем, что пользователь - владелец проекта
        $isOwner = $project->users()->where('user_id', $user->id)->first()->pivot->role_in_project === 'owner';

        if (!$isOwner) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Только владелец проекта может редактировать описание.'], 403);
            }
            abort(403, 'Только владелец проекта может редактировать описание.');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string',
            'description' => 'nullable|string',
        ]);
        $project->update($validated);

        return response()->json(['success' => true, 'message' => 'Описание успешно обновлено']);
    }

    public function delete(Request $request, Project $project)
    {
        $user = Auth::user();

        // Проверяем, что пользователь - владелец проекта
        $isOwner = $project->users()->where('user_id', $user->id)->first()->pivot->role_in_project === 'owner';

        if (!$isOwner) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Только владелец проекта может удалить проект.'], 403);
            }
            abort(403, 'Только владелец проекта может удалить проект.');
        }

        $projectId = $project->project_id;

        $tasksIds = $project->tasks->pluck('task_id');

        AiInteraction::whereIn('task_id', $tasksIds)->delete();

        Comment::whereIn('task_id', $tasksIds)->delete();

        TaskHistory::whereIn('task_id', $tasksIds)->delete();

        Task::whereIn('task_id', $tasksIds)->each(function ($task) {
            $task->users()->detach();
        });

        Task::where('project_id', $projectId)->delete();

        $project->users()->detach();

        AiInteraction::where('project_id', $projectId)->delete();

        $project->delete();

        // Получить первый доступный проект пользователя для редиректа
        $firstProject = $user->projects()->first();

        return response()->json([
            'success' => true,
            'redirect' => route('dashboard', ['project_id' => $firstProject?->project_id])
        ]);
    }

    public function leave(Project $project)
    {
        $user = Auth::user();

        $isOwner = $project->users()->where('user_id', $user->id)->first()->pivot->role_in_project === 'owner';

        if ($isOwner) {
            // Проверяем, есть ли другие пользователи в проекте
            $otherOwners = $project->users()->where('user_id', '!=', $user->id)
                ->wherePivot('role_in_project', 'owner')->count();

            if ($otherOwners === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Вы единственный владелец проекта. Сначала назначьте другого владельца или удалите проект.'
                ], 403);
            }
        }

        // Удалить пользователя из проекта
        $project->users()->detach($user->id);

        // Получить первый доступный проект пользователя
        $firstProject = $user->projects()->first();

        return response()->json([
            'success' => true,
            'redirect' => route('dashboard', ['project_id' => $firstProject?->project_id])
        ]);
    }

    // Добавление участника
    public function addMember(Request $request, Project $project)
    {
        $user = Auth::user();

        // Проверка что пользователь - учатсник проекта
        $isOwner = $project->users()->where('user_id', $user->id)->first()->pivot->role_in_project === 'owner';

        if (!$isOwner) {
            return response()->json([
                'success' => false,
                'message' => 'Только владелец проекта может добавлять пользователей.'
            ], 403);
        }

        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        $newUser = User::where('email', $request->email)->first();

        // Проверяем, не состоит ли пользователь уже в проекте
        if ($project->users->contains($newUser)) {
            return response()->json([
                'success' => false,
                'message' => 'Пользователь уже состоит в проекте'
            ], 422);
        }

        // Добавляем пользователя с ролью member
        $project->users()->attach($newUser->id, ['role_in_project' => 'member']);

        return response()->json([
            'success' => true,
            'message' => 'Участник успешно добавлен.',
            'user' => ['id' => $newUser->id, 'name' => $newUser->name],
        ]);
    }

    // Удаление участника
    public function deleteMember(Project $project, User $user)
    {
        $currentUser = Auth::user();

        // Проверка что пользователь - владелец проекта
        $isOwner = $project->users()->where('user_id', $currentUser->id)->first()->pivot->role_in_project === 'owner';

        if (!$isOwner) {
            return response()->json([
                'success' => false,
                'message' => 'Только владелец проекта может удалять пользователей.'
            ], 403);
        }

        // Нельзя удалить владельца
        $isTargetOwner = $project->users()->where('user_id', $user->id)->first()->pivot->role_in_project === 'owner';
        if ($isTargetOwner) {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя удалить владельца проекта. Сначала измените его роль.'
            ], 403);
        }

        // Нельзя удалить самого себя (используйте выход из проекта)
        if ($user->id === $currentUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'Используйте кнопку "Выйти из проекта"'
            ], 403);
        }

        $project->users()->detach($user->id);

        return response()->json([
            'success' => true,
            'message' => 'Участник удален.'
        ]);
    }

    // Изменение роли участника
    public function changeRole(Request $request, Project $project, User $user)
    {
        $currentUser = Auth::user();

        // Проверка что пользователь - владелец проекта
        $isOwner = $project->users()->where('user_id', $currentUser->id)->first()->pivot->role_in_project === 'owner';

        if (!$isOwner) {
            return response()->json([
                'success' => false,
                'message' => 'Только владелец проекта может менять роль пользователей.'
            ], 403);
        }

        $request->validate([
            'role' => 'required|in:member,owner'
        ]);

        // Нельзя изменить роль владельца
        if ($user->id === $currentUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя изменить свою роль'
            ], 403);
        }

        // Обновить роль
        $project->users()->updateExistingPivot($user->id, ['role_in_project' => $request->role]);

        return response()->json([
            'success' => true,
            'message' => 'Роль изменена.'
        ]);
    }

    public function getUsers(Project $project)
    {
        return response()->json($project->users->map(fn($u) => [
            'id' => $u->id,
            'name' => $u->name,
        ]));
    }

    public function aiAnalyze(Project $project)
    {
        $user = Auth::user();

        $member = $project->users()->where('user_id', $user->id)->exists();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Нет доступа',
            ], 403);
        }

        $stats = [
            'todo' => $project->tasks->where('status', 'todo')->count(),
            'in_progress' => $project->tasks->where('status', 'in_progress')->count(),
            'done' => $project->tasks->where('status', 'done')->count(),
            'overdue' => $project->tasks->filter(fn($t) => $t->due_date && $t->due_date < now() && $t->status !== 'done')->count(),
        ];

        // Список задач с приоритетами и сроками
        $taskList = $project->tasks->map(fn($t) => [
            'name' => $t->name,
            'priority' => $t->priority,
            'status' => $t->status,
            'due_date' => $t->due_date?->format('d.m.Y'),
        ])->toArray();

        // Нагрузка на участников
        $membersLoad = $project->users->map(fn($u) => [
            'name' => $u->name,
            'tasks_count' => $u->tasks()->where('project_id', $project->project_id)->count(),
        ])->toArray();

        $prompt = "Ты — аналитик проектов. Проанализируй проект '{$project->name}'.\n\n";
        $prompt .= "Статистика проекта:\n";
        $prompt .= "- К выполнению: {$stats['todo']} задач\n";
        $prompt .= "- В работе: {$stats['in_progress']} задач\n";
        $prompt .= "- Завершено: {$stats['done']} задач\n";
        $prompt .= "- Просрочено: {$stats['overdue']} задач\n\n";

        $prompt .= "Задачи с приоритетами и сроками:\n";
        foreach ($taskList as $task) {
            $prompt .= "- {$task['name']} (приоритет: {$task['priority']}, статус: {$task['status']}, 
            срок: {$task['due_date']})\n";
        }

        $prompt .= "\nНагрузка на участников:\n";
        foreach ($membersLoad as $member) {
            $prompt .= "- {$member['name']}: {$member['tasks_count']} задач\n";
        }

        $prompt .= "Проанализируй следующие аспекты:\n";
        $prompt .= "1. Сроки выполнения: оцени риск срыва сроков\n";
        $prompt .= "2. Нагрузка на команду: на кого какая нагрузка\n";
        $prompt .= "3. Приоритеты: какие задачи требуют внимания в первую очередь\n";
        $prompt .= "4. Общая динамика: темп выполнения проекта\n\n";

        $prompt .= "Дай ровно 4 короткие рекомендации. Каждая рекомендация — одно предложение. 
        Используй эмодзи в начале (⚠️, 📊, 🔥, ✅, 🚨, 🎯). 
        ВАЖНО: не используй кавычки внутри текста рекомендаций.
        Формат ответа: только массив JSON, без пояснений, без markdown. 
        Пример: [\"⚠️ Срочно обработать просроченные задачи\", \"📊 Перераспределить нагрузку\"]";

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
            // Если AI не вернул JSON — разбиваем по строкам
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
            'project_id' => $project->project_id,
            'request_type' => 'project_analysis',
            'input_data' => json_encode(['stats' => $stats, 'name' => $project->name]),
            'output_data' => $outputForDB,
            'request_date' => now(),
        ]);

        return response()->json([
            'success' => true,
            'analysis' => $output,
        ]);
    }
}
