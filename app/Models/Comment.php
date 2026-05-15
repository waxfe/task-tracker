<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Листинг модели комментария
class Comment extends Model
{
    protected $primaryKey = 'comment_id';
    protected $fillable = ['text', 'task_id', 'user_id'];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
