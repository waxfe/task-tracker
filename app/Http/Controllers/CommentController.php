<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function store(Request $request, $id)
    {
        $task = Task::findOrFail($id);
        $user = Auth::user();
        $project = $task->project;

        $member = $project->users()->where('user_id', $user->id)->first();
        $isProjectmember = (bool) $member;
        $isExecutor = $task->users()->where('user_id', $user->id)->exists();

        if (!$isProjectmember && !$isExecutor) {
            return response()->json([
                'success' => false,
                'message' => 'Нет доступа к созданию комментария',
            ], 403);
        }

        $request->validate([
            'text' => 'required|string',
        ]);

        $comment = Comment::create([
            'text' => $request->text,
            'user_id' => $user->id,
            'task_id' => $task->task_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Комментарий добавлен',
            'comment' => [
                'id' => $comment->comment_id,
                'text' => $comment->text,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
                'created_at' => $comment->created_at->format('d.m.Y'),
            ],
        ], 201);
    }

    public function update(Request $request, Task $task, $commentId)
    {
        $comment = $task->comments()->findOrFail($commentId);
        $user = Auth::user();

        $isOwner = $comment->task->project->users()
            ->where('user_id', $user->id)
            ->first()
            ?->pivot->role_in_project === 'owner';
        $isExecutor = $comment->task->users->contains($user);
        $isAuthor = $comment->user_id === $user->id;

        if (!$isOwner && !$isExecutor && !$isAuthor) {
            return response()->json([
                'success' => false,
                'message' => 'Нет прав для изменения комментария',
            ], 403);
        }

        $request->validate([
            'text' => 'required|string',
        ]);

        $comment->text = $request->text;
        $comment->save();

        return response()->json([
            'success' => true,
            'message' => 'Комментарий обновлен.',
            'comment' => [
                'id' => $comment->comment_id,
                'text' => $comment->text,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
                'updated_at' => $comment->updated_at->format('d.m.Y H:i'),
            ],
        ]);
    }

    public function destroy(Task $task, $commentId)
    {
        $comment = $task->comments()->findOrFail($commentId);
        $user = Auth::user();

        $isOwner = $comment->task->project->users()
            ->where('user_id', $user->id)
            ->first()->pivot?->role_in_project === 'owner';
        $isAuthor = $comment->user_id === $user->id;
        $isExecutor = $comment->task->users->contains($user);

        if (!$isAuthor && !$isExecutor && !$isOwner) {
            return response()->json([
                'success' => false,
                'message' => 'Нет прав для удаления.',
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Комментерий удален.',
        ]);
    }
}
