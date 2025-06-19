<?php

namespace App\Http\Resources;

use App\Http\Resources\Auth\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $totalTasks = $this->tasks->count();
        $completedTasks = $this->tasks->where('status', 'completed')->count();
        $completionPercentage = $totalTasks > 0
            ? round(($completedTasks / $totalTasks) * 100, 2)
            : 0;

        $start = $this->start_date;
        $end = $this->end_date;
        $categories = [];

        if ($this->status === 'completed') {
            $categories[] = 'completed';
        }
        if ($end && $end->between(now(), now()->addDays(5)) && $this->status !== 'completed') {
            $categories[] = 'deadline';
        }
        if ($this->status === 'upcoming' || ($start && $start > now())) {
            $categories[] = 'upcoming';
        }
        if ($this->status === 'in-progress') {
            $categories[] = 'in-progress';
        }
        if (empty($categories)) {
            $categories[] = 'other';
        }

        return [
            'id' => $this->nano_id,
            'admin' => new UserResource($this->admin),
            'name' => $this->name,
            'image' => $this->image ? asset($this->image) : null,
            'description' => $this->description,
            'status' => $this->status,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'completion_percentage' => $completionPercentage,
            'category' => implode(', ', $categories),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
