<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskUser extends Model
{
    /** @use HasFactory<\Database\Factories\TaskUserFactory> */
    use HasFactory;

    protected $table = 'task_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'task_id',
        'user_id',
    ];

    /**
     * Get the task associated with the task_users.
     */
    public function task()
    {
        return $this->belongsTo(Task::class)->withDefault();
    }
    
    /**
     * Get the user associated with the task_users.
     */
    public function user()
    {
        return $this->belongsTo(User::class)->withDefault();
    }
}
