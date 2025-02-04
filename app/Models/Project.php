<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory, HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'admin_id',
        'name',
        'image',
        'description',
        'status',
        'start_date',
        'end_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    /**
     * Get the admin associated with the project.
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id')->withDefault();
    }

    /**
     * Get the collaborators associated with the project.
     */
    public function collaborators(): HasMany
    {
        return $this->hasMany(Collaborator::class);
    }

    /**
     * Get the users (collaborators) associated with the project through the collaborators table.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'collaborators');
    }

    /**
     * Get the tasks associated with the project.
     */
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

}
