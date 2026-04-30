<?php

namespace App\Models;

use App\Models\Comment as ModelsComment;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $primaryKey = 'task_id';
    protected $fillable = ['name', 'description', 'status', 'priority', 'due_date', 'project_id'];

    protected $casts = ['due_date' => 'date'];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'task_user', 'task_id', 'user_id')
            ->withPivot('assignment_date')
            ->withTimestamps();
    }

    public function comments()
    {
        return $this->hasMany(ModelsComment::class, 'task_id');
    }

    public function histories()
    {
        return $this->hasMany(TaskHistory::class, 'task_id');
    }

    public function aiInteractions()
    {
        return $this->hasMany(AiInteraction::class, 'task_id');
    }
}
