<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'phone',
        'image',
    ];

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
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the projects admined by a user.
     */
    public function adminProjects()
    {
        return $this->hasMany(Project::class, 'admin_id');
    }

    /**
     * Get the collaborations a user has.
     */
    public function collaborations()
    {
        return $this->hasMany(Collaborator::class);
    }

    /**
     * Get the projects a user has collaborated with through collaborators table.
     */
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'collaborators');
    }

    /**
     * Get the collaborators of a project admined by a user.
     */
    public function projectCollaborators()
    {
        return $this->hasManyThrough(Collaborator::class, Project::class, 'admin_id', 'project_id', 'id', 'id');
    }
    
    /**
     * Get all projects the user is involded in either as an admin or collaborator
     */
    public function allProjects()
    {
        $adminProjects = $this->adminProjects()->select('projects.*');
        $collaboratorProjects = $this->projects()->select('projects.*');

        return $adminProjects->union($collaboratorProjects)->orderBy('created_at', 'desc');
    }

    /**
     * Get the tasks associated with the user.
     */
    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'task_users', 'user_id', 'task_id');
    }

}
    