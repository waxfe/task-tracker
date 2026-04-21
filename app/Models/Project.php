<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;
    protected $primaryKey = 'project_id';
    protected $fillable = ['name', 'description'];

    public function tasks()
    {
        return $this->hasMany(Task::class, 'project_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id', 'user_id')
            ->withPivot('role_in_project')
            ->withTimestamps();
    }

    public function aiInteractions()
    {
        return $this->hasMany(AiInteraction::class, 'project_id');
    }
}
