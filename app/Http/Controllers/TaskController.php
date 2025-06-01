<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Notifications\TaskSubmitted;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

use function Laravel\Prompts\info;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Project $project)
    {
        $projectTasks = $project->tasks;

        return response()->json([
            'data' => TaskResource::collection($projectTasks),
            'message' => 'Project tasks retrieved successfully',
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTaskRequest $request)
    {
        $validated = $request->validated();
        
        $project = Project::where('nano_id', $validated['project_id'])->firstOrFail();
        Gate::authorize('create', $project);
        
        $task = Task::create($validated);

        return response()->json([
            'data' => new TaskResource($task),
            'message' => 'Task created successfully',
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task)
    {
        $task->load(['users', 'project']);
        Gate::authorize('view', $task);

        return response()->json([
            'data' => new TaskResource($task),
            'messsage' => 'Task retrieved successfully',
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTaskRequest $request, Task $task)
    {
        $task->load(['users', 'project']);
        Gate::authorize('update', $task);

        $validated = $request->validated();
        info(count($validated));
        $task->update($validated);

        return response()->json([
            'data' => new TaskResource($task),
            'message' => 'Task has been updated successfully',
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        $task->load('project');
        Gate::authorize('delete', $task);

        // Delete any associated assignees
        $task->users()->detach();
        // Finally, delete the task
        $task->delete();

        return response()->json([
            'message' => 'Task has been deleted successfully',
        ], Response::HTTP_OK);
    }

    /**
     * Update task status.
     */
        public function updateStatus(UpdateTaskStatusRequest $request, Task $task)
    {
        Gate::authorize('update', $task);

        $validated = $request->validated();
        $status = $validated['status'];

        $task->load('project');

        // If status is already the same, avoid updating
        if ($task->status === $status) {
            return response()->json([
                'message' => "Task is already marked as {$status}.",
            ], Response::HTTP_OK);
        }

        // Only the project admin can mark a task as completed
        if ($status === 'completed') {
            $user = Auth::user();
            $isAdmin = $task->project->admin_id === $user->id;

            if (! $isAdmin) {
                return response()->json([
                    'message' => 'Only the project admin can mark a task as completed.',
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $task->status = $status;
        $task->save();

        // Notify only if marked as pending
        if ($status === 'pending') {
            $user = Auth::user();
            defer(fn() => $user->notify(new TaskSubmitted(
                $task->project->name,
                'Notification_url'
            )));
        }

        return response()->json([
            'message' => "Task marked as {$status} successfully.",
        ], Response::HTTP_OK);
    }
}
