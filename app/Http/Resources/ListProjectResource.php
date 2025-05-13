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

        $today = now()->toDateString();
        $start = $this->start_date;
        $end = $this->end_date;

        // Determine project category
        if ($completionPercentage === 100) {
            $category = 'completed';
        } elseif ($this->status === 'upcoming' && $today < $start) {
            $category = 'upcoming';
        } elseif ($today === $end && $completionPercentage < 100) {
            $category = 'deadline';
        } elseif ($today >= $start && $today <= $end) {
            $category = 'ongoing';
        } else {
            $category = 'unknown'; // fallback
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
            'category' => $category,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
