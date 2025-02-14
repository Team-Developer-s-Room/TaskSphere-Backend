<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskUser extends Model
{
    /** @use HasFactory<\Database\Factories\TaskUserFactory> */
    use HasFactory, HasUlids;

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
     * Get the columns that should receive a unique identifier.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['nano_id'];
    }

    /**
     * Get the route key for the model
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'nano_id';
    }

    /**
     * Get the value of the model's route key
     *
     * @return mixed
     */
    public function getRouteKey(): mixed
    {
        return $this->nano_id;
    }

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
