<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Requests\UpdateProjectStatusRequest;
use App\Http\Resources\ListProjectResource;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\SuperProjectResource;
use App\Notifications\ProjectCreated;
use App\Notifications\ProjectUpdated;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

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
                'all_projects' => ListProjectResource::collection($projects),
                'admin_projects' => ListProjectResource::collection($adminProjects),
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
        // if ($request->hasFile('image')) {
        //     $imagePath = $request->file('image')->store('project-images', 'public');
        //     $validated['image'] = $imagePath;
        // }

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('', 'public_folder');
            $validated['image'] = 'project-images/' . $path;
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

        if ($request->hasFile('image')) {
            // Delete the old image if it exists
            if ($project->image && Storage::disk('public_folder')->exists(str_replace('project-images/', '', $project->image))) {
                Storage::disk('public_folder')->delete(str_replace('project-images/', '', $project->image));
            }

            // Store the new image
            $path = $request->file('image')->store('', 'public_folder');
            $validated['image'] = 'project-images/' . $path;
        }

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
     * Update project status.
     */
    public function updateStatus(UpdateProjectStatusRequest $request, Project $project)
    {
        Gate::authorize('update', $project);

        $validated = $request->validated();
        $status = $validated['status'];

        // If status is the same, avoid updating
        if ($project->status === $status) {
            return response()->json([
                'message' => "Project is already marked as {$status}.",
            ], Response::HTTP_OK);
        }

        // Validate upcoming status logic
        if ($status === 'upcoming') {
            if (! $project->start_date || now()->gte($project->start_date)) {
                return response()->json([
                    'message' => 'Cannot mark project as upcoming. The start date has already passed.',
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        // Validate in-progress status logic
        if ($status === 'in-progress') {
            if ($project->start_date && now()->lt($project->start_date)) {
                return response()->json([
                    'message' => 'Cannot mark project as in-progress. The start date is in the future.',
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        // Validate completed status logic
        if ($status === 'completed') {
            $project->load('tasks');

            if ($project->tasks->isEmpty()) {
                return response()->json([
                    'message' => 'Cannot mark project as completed. Project has no tasks.',
                ], Response::HTTP_BAD_REQUEST);
            }

            $allTasksCompleted = $project->tasks->every(fn($task) => $task->status === 'completed');

            if (! $allTasksCompleted) {
                return response()->json([
                    'message' => 'Cannot mark project as completed. Not all tasks are completed.',
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        $project->status = $status;
        $project->save();

        return response()->json([
            'message' => "Project marked as {$status} successfully.",
        ], Response::HTTP_OK);
    }
}
