<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\SuperTaskResource;
use Illuminate\Http\Response;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    // public function index()
    // {
    //     $userId = auth()->id();
    //     $now = Carbon::now();

    //     $projects = Project::with(['admin', 'users', 'tasks'])
    //         ->where('admin_id', $userId)
    //         ->orWhereHas('collaborators', fn($q) => $q->where('user_id', $userId))
    //         ->get();

    //     $categorized = [
    //         'ongoing' => [],
    //         'upcoming' => [],
    //         'nearing_deadline' => [],
    //         'completed' => [],
    //     ];

    //     foreach ($projects as $project) {
    //         $start = Carbon::parse($project->start_date);
    //         $deadline = Carbon::parse($project->end_date);
    //         $resource = new ProjectResource($project);

    //         if ($project->status === 'completed') {
    //             $categorized['completed'][] = $resource;
    //         } elseif ($start > $now) {
    //             $categorized['upcoming'][] = $resource;
    //         } elseif ($start <= $now && $deadline >= $now) {
    //             if ($deadline->diffInDays($now) <= 7) {
    //                 $categorized['nearing_deadline'][] = $resource;
    //             } else {
    //                 $categorized['ongoing'][] = $resource;
    //             }
    //         }
    //     }

    //     return response()->json([
    //         'data' => $categorized,
    //         'message' => 'Dashboard projects categorized successfully',
    //     ], Response::HTTP_OK);
    // }

    public function monthlyPercentageTaskSummary()
    {
        $user = Auth::user();

        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        // Fetch tasks assigned to the user this month based on the pivot created_at
        $assignedTasks = $user->tasks()
            ->whereBetween('task_users.created_at', [$startOfMonth, $endOfMonth]);

        $total = $assignedTasks->count();
        $done = (clone $assignedTasks)->whereIn('status', ['pending', 'completed'])->count();
        $notDone = (clone $assignedTasks)->whereNull('status')->count();

        $percentage = $total > 0 ? round(($done / $total) * 100, 2) : 0;

        return response()->json([
            'total' => $total,
            'done' => $done,
            'not_done' => $notDone,
            'completion_rate' => $percentage . '%',
        ], Response::HTTP_OK);
    }

    public function assignedTasks(Request $request)
    {
        $mode = $request->query('mode', 'latest');

        $query = Auth::user()->tasks()
            ->whereNull('status')
            ->latest()
            ->with('project');

        $total = $query->count();

        $tasks = strtolower($mode) === 'latest'
            ? $query->limit(8)->get()
            : $query->paginate(15);

        return response()->json([
            'data' => SuperTaskResource::collection($tasks),
            'mode' => $mode,
            'total' => $total,
        ], Response::HTTP_OK);
    }

    public function todayDeadlineTasks(Request $request)
    {
        $mode = $request->query('mode', 'latest');

        $query = Auth::user()->tasks()
            ->whereDate('end_date', Carbon::today())
            ->latest();

        $tasks = strtolower($mode) === 'latest'
            ? $query->limit(8)->get()
            : $query->paginate(15);

        return response()->json([
            'data' => SuperTaskResource::collection($tasks),
            'mode' => $mode,
        ], Response::HTTP_OK);
    }

    public function deadlineProjects(Request $request)
    {
        $mode = $request->query('mode', 'latest');
        $query = Auth::user()->allProjects()
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [now(), now()->addDays(5)])
            ->where('status', '!=', 'completed')
            ->latest();

        $projects = strtolower($mode) === 'latest'
            ? $query->limit(8)->get()
            : $query->paginate(15);

        return response()->json([
            'data' => ProjectResource::collection($projects),
            'mode' => $mode,
        ], Response::HTTP_OK);
    }

    public function upcomingProjects(Request $request)
    {
        $mode = $request->query('mode', 'latest');

        $query = Auth::user()->allProjects()
            ->where(function ($query) {
                $query->whereDate('start_date', '>', now())
                    ->orWhere('status', 'upcoming');
            })
            ->latest();

        $projects = strtolower($mode) === 'latest'
            ? $query->limit(8)->get()
            : $query->paginate(15);

        return response()->json([
            'data' => ProjectResource::collection($projects),
            'mode' => $mode,
        ], Response::HTTP_OK);
    }

    public function completedProjects(Request $request)
    {
        $mode = $request->query('mode', 'latest');

        $query = Auth::user()->allProjects()
            ->where('status', 'completed')
            ->latest();

        $projects = strtolower($mode) === 'latest'
            ? $query->limit(8)->get()
            : $query->paginate(15);

        return response()->json([
            'data' => ProjectResource::collection($projects),
            'mode' => $mode,
        ], Response::HTTP_OK);
    }

    // For the chart
    public function weeklyCompletedTasks()
    {
        $user = Auth::user();

        // Get current week's Monday and Sunday
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        // Fetch tasks completed this week, grouped by date
        $tasks = $user->tasks()
            ->where('status', 'completed')
            ->whereBetween('tasks.updated_at', [$startOfWeek, $endOfWeek])
            ->selectRaw('DATE(tasks.updated_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        // Build the response with day names
        $dailyBreakdown = [];
        for ($date = $startOfWeek->copy(); $date <= $endOfWeek; $date->addDay()) {
            $dayName = $date->format('l'); // e.g., Monday
            $dateKey = $date->toDateString();
            $dailyBreakdown[$dayName] = $tasks[$dateKey] ?? 0;
        }

        return response()->json([
            'weekly_task_completion' => $dailyBreakdown,
            'from' => $startOfWeek->toDateString(),
            'to' => $endOfWeek->toDateString(),
        ], Response::HTTP_OK);
    }

    // For the calendar
    public function weeklyTaskSummary()
    {
        $user = Auth::user();

        // Get Monday and Sunday of the current week
        $monday = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $sunday = Carbon::now()->endOfWeek(Carbon::SUNDAY);

        // Get all tasks for the user that either start or end this week
        $tasks = $user->tasks()
            ->where(function ($query) use ($monday, $sunday) {
                $query->whereBetween('start_date', [$monday, $sunday])
                    ->orWhereBetween('end_date', [$monday, $sunday]);
            })
            ->get();

        $summary = [];

        // Loop through each day of the week
        for ($date = $monday->copy(); $date->lte($sunday); $date->addDay()) {
            $startCount = $tasks->where('start_date', $date->toDateString())->count();
            $endCount = $tasks->where('end_date', $date->toDateString())->count();

            $dayName = $date->format('l'); // Full day name (e.g., Monday)

            if ($startCount === 0 && $endCount === 0) {
                $summary[$dayName] = null;
            } else {
                $summary[$dayName] = "You have {$startCount} task(s) starting today and {$endCount} task(s) ending today.";
            }
        }

        return response()->json($summary, Response::HTTP_OK);
    }
}
