<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

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
        $task->load(['collaborators', 'project']);
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
        $task->load(['collaborators', 'project']);
        Gate::authorize('update', $task);

        $validated = $request->validated();

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
}
