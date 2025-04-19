<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Http\Resources\ProjectResource;
use Illuminate\Http\Response;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = auth()->id();
        $now = Carbon::now();

        $projects = Project::with(['admin', 'users', 'tasks'])
            ->where('admin_id', $userId)
            ->orWhereHas('collaborators', fn ($q) => $q->where('user_id', $userId))
            ->get();

        $categorized = [
            'ongoing' => [],
            'upcoming' => [],
            'nearing_deadline' => [],
            'completed' => [],
        ];

        foreach ($projects as $project) {
            $start = Carbon::parse($project->start_date);
            $deadline = Carbon::parse($project->end_date);
            $resource = new ProjectResource($project);

            if ($project->status === 'completed') {
                $categorized['completed'][] = $resource;
            } elseif ($start > $now) {
                $categorized['upcoming'][] = $resource;
            } elseif ($start <= $now && $deadline >= $now) {
                if ($deadline->diffInDays($now) <= 7) {
                    $categorized['nearing_deadline'][] = $resource;
                } else {
                    $categorized['ongoing'][] = $resource;
                }
            }
        }

        return response()->json([
            'data' => $categorized,
            'message' => 'Dashboard projects categorized successfully',
        ], Response::HTTP_OK);
    }
}

