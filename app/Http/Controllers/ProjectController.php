<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use Illuminate\Http\Response;

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
        // TODO
        // Eager load associated admin
        // Eager load collaborators
        // Eager load associated tasks
        // $project->load();

        return response()->json([
            'data' => new ProjectResource($project),
            'message' => 'Project retrieved successfully',
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project)
    {
        // TODO
        // Task details can also be updated
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        // TODO
        // Remove associated tasks
    }
}
