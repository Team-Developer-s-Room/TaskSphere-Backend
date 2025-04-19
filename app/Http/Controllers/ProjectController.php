<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\SuperProjectResource;
use App\Notifications\ProjectCreated;
use App\Notifications\ProjectUpdated;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

use function Illuminate\Support\defer;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userId = auth()->id();

        $projects = Project::with(['admin', 'collaborators', 'tasks'])
            ->where('admin_id', $userId) // Projects where user is an admin
            ->orWhereHas('collaborators', function ($query) use ($userId) {
                $query->where('user_id', $userId); // Projects where user is a collaborator
            })
            ->get();

        $adminProjects = $projects->where('admin_id', $userId)->values();

        return response()->json([
            'data' => [
                'all_projects' => ProjectResource::collection($projects),
                'admin_projects' => ProjectResource::collection($adminProjects),
            ],
            'message' => 'Projects retrieved successfully',
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request)
    {
        $validated = $request->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('project-images', 'public');
            $validated['image'] = $imagePath;
        }

        $project = Project::create($validated);
        $user = Auth::user(); // Project Admin

        defer(fn() => $user->notify(new ProjectCreated(
            $project->name,
            'Notification_url'
        )));

        return response()->json([
            'data' => new ProjectResource($project),
            'message' => 'Project created successfully',
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project)
    {
        $project->load(['collaborators', 'admin', 'users', 'tasks']);
        Gate::authorize('view', $project);

        return response()->json([
            'data' => new SuperProjectResource($project),
            'message' => 'Project retrieved successfully',
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project)
    {
        Gate::authorize('update', $project);

        $validated = $request->validated();

        $project->update($validated);

        $users = $project->users->push($project->admin);
        defer(fn() => Notification::send($users, new ProjectUpdated(
            $project->name,
            'Notification_url'
        )));

        return response()->json([
            'data' => new ProjectResource($project),
            'message' => 'Project updated successfully',
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        Gate::authorize('delete', $project);

        // Delete associated tasks
        $project->tasks()->delete();
        // Delete associated collaborators
        $project->collaborators()->delete();
        // Detach users if using many-to-many relationship
        $project->users()->detach();
        // Finally, delete the project
        $project->delete();

        return response()->json([
            'message' => 'Project deleted successfully',
        ], Response::HTTP_OK);
    }

    /**
     * Mark a project as completed.
     */
    public function markAsCompleted(Project $project)
    {
        // Ensure the user is authorized to update the project
        Gate::authorize('update', $project);
        
        // Check if all tasks in the project are completed
        $allTasksCompleted = $project->tasks->every(function ($task) {
            return $task->status === 'completed';
        });

        // If not all tasks are completed, return an error response
        if (!$allTasksCompleted) {
            return response()->json([
                'message' => 'Cannot mark project as completed. Not all tasks are completed.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Mark the project as completed
        $project->status = 'completed';
        $project->save();

        return response()->json([
            'message' => 'Project marked as completed successfully.',
        ], Response::HTTP_OK);
    }
}
