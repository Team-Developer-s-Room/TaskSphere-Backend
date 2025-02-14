<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    /** @use HasFactory<\Database\Factories\TaskFactory> */
    use HasFactory, HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'description',
        'status',
        'start_time',
        'end_time',
        'start_date',
        'end_date',
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
     * Get the project the task is associated to.
     */
    public function project()
    {
        return $this->belongsTo(Project::class)->withDefault();
    }

    /**
     * Get the users associated with the task.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'task_users', 'task_id', 'user_id');
    }

}
