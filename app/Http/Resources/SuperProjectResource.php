<?php

namespace App\Http\Resources;

use App\Http\Resources\Auth\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SuperProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $totalTasks = $this->tasks->count();
        $completedTasks = $this->tasks->where('status', 'completed')->count(); // Adjust status if necessary
        $completionPercentage = $totalTasks > 0
            ? round(($completedTasks / $totalTasks) * 100, 2)
            : 0;

        return [
            'id' => $this->nano_id,
            'admin' => new UserResource($this->admin),
            'name' => $this->name,
            'image' => $this->image,
            'description' => $this->description,
            'status' => $this->status,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'completion_percentage' => $completionPercentage,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'users' => UserResource::collection($this->users),
            'tasks' => TaskResource::collection($this->tasks),
        ];
    }
}
