<?php

namespace App\Http\Controllers;

use App\Models\AiInteraction;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function show(Project $project)
    {
        $user = Auth::user();

        if (!$project->users->contains($user)) {
            abort(403, 'У вас нет доступа к этому проекту.');
        }

        $projects = $user->projects;
        $selectedProject = $project;

        // Сначала получаем задачи через get()
        $tasks = $project->tasks()->with('users', 'aiInteractions')->get();

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

        return view('projects.show', compact('project', 'projects', 'selectedProject', 'tasks', 'stats', 'members', 'currentUser', 'isOwner'));
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

        $project->description = $request->description;
        $project->save();

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

    public function leave(Request $request, Project $project)
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
}
