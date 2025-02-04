<?php

namespace App\Http\Controllers;

use App\Http\Requests\RemoveCollaboratorRequest;
use App\Models\Collaborator;
use App\Http\Requests\StoreCollaboratorRequest;
use App\Http\Requests\UpdateCollaboratorRequest;
use App\Http\Resources\Auth\UserResource;
use App\Models\Project;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CollaboratorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Project $project)
    {
        $users = $project->users;

        return response()->json([
            'data' => UserResource::collection($users),
            'message' => 'Collaborators retrieved successfully',
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCollaboratorRequest $request, Project $project)
    {
        Gate::authorize('create', $project);
        
        $validated = $request->validated();
        $validated['project_id'] = $project->id;

        $collaborator = Collaborator::create($validated);
        $user = $collaborator->user;

        return response()->json([
            'data' => new UserResource($user),
            'message' => 'Collaborator added successfully',
        ], Response::HTTP_CREATED);
    }

    // /**
    //  * Display the specified resource.
    //  */
    // public function show(Collaborator $collaborator)
    // {
    //     $user = $collaborator->user;

    //     return response()->json([
    //         'data' => new UserResource($user),
    //         'message' => 'Collaborator retrieved successfully',
    //     ], Response::HTTP_OK);
    // }

    // /**
    //  * Update the specified resource in storage.
    //  */
    // public function update(UpdateCollaboratorRequest $request, Collaborator $collaborator)
    // {
    //     $validated = $request->validated();

    //     $collaborator->update($validated);
    //     $user = $collaborator->user;

    //     return response()->json([
    //         'data' => new UserResource($user),
    //         'message' => 'Collaborator retrieved successfully',
    //     ], Response::HTTP_OK);
    // }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RemoveCollaboratorRequest $request, Project $project)
    {
        Gate::authorize('delete', $project);
        
        $validated = $request->validated();

        Collaborator::where('project_id', $project->id)
        ->where('user_id', $validated['user_id'])
        ->delete();

        return response()->json([
            'message' => 'Collaborator deleted successfully',
        ], Response::HTTP_NO_CONTENT);
    }
}
