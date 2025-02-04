<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\SuperProjectResource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $projects = Project::with(['admin'])->get();

        return response()->json([
            'data' => ProjectResource::collection($projects),
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
        $project->load(['collaborators', 'admin', 'users:image']);
        Gate::authorize('view', $project);

        return response()->json([
            'data' => new ProjectResource($project),
            'message' => 'Project retrieved successfully',
        ], Response::HTTP_OK);
    }

    /**
     * Display the specified resource in more detail.
     */
    public function showDetailed(Project $project)
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
}
