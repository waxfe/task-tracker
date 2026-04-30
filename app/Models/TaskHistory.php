<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskHistory extends Model
{
    protected $primaryKey = 'history_id';
    protected $fillable = ['changed_field', 'old_value', 'new_value', 'task_id', 'user_id'];

    protected $casts = [
        'change_date' => 'datetime',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
