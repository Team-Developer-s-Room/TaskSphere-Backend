<?php

namespace App\Http\Resources;

use App\Http\Resources\Auth\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollaboratorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => new UserResource($this->user),
            'project' => new ProjectResource($this->project),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
