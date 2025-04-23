<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
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
        
        $project = Project::findOrFail($validated['project_id']);
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
     * Mark task as pending
     */
    public function markAsPending(Task $task)
    {
        Gate::authorize('update', $task);

        // Ensure the project relationship is loaded
        $task->load('project');

        $task->status = 'pending';
        $task->save();

        $user = Auth::user();
        defer(fn() => $user->notify(new TaskSubmitted(
            $task->project->name,
            'Notification_url'
        )));

        return response()->json([
            'message' => 'Task marked as pending successfully',
        ], Response::HTTP_OK);
    }

    /**
     * Mark task as completed
     */
    public function markAsCompleted(Task $task)
    {
        // Check if the user is authorized to update the task (e.g., admin)
        Gate::authorize('update', $task);

        // Update the task's status to "completed"
        $task->status = 'completed';
        $task->save();

        return response()->json([
            'message' => 'Task marked as completed successfully',
        ], Response::HTTP_OK);
    }
}
