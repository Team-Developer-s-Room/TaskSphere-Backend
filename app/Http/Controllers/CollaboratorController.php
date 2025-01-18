<?php

namespace App\Http\Controllers;

use App\Models\Collaborator;
use App\Http\Requests\StoreCollaboratorRequest;
use App\Http\Requests\UpdateCollaboratorRequest;
use App\Http\Resources\CollaboratorResource;
use Illuminate\Http\Response;

class CollaboratorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $collaborators = Collaborator::with(['user', 'project'])->get();

        return response()->json([
            'data' => CollaboratorResource::collection($collaborators),
            'message' => 'Collaborators retrieved successfully',
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCollaboratorRequest $request)
    {
        $validated = $request->validated();

        $collaborator = Collaborator::create($validated);
        $collaborator->load(['user', 'project']);

        return response()->json([
            'data' => new CollaboratorResource($collaborator),
            'message' => 'Collaborator added successfully',
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Collaborator $collaborator)
    {
        $collaborator->load(['user', 'project']);

        return response()->json([
            'data' => new CollaboratorResource($collaborator),
            'message' => 'Collaborator retrieved successfully',
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCollaboratorRequest $request, Collaborator $collaborator)
    {
        $validated = $request->validated();

        $collaborator->update($validated);
        $collaborator->load(['user', 'project']);

        return response()->json([
            'data' => new CollaboratorResource($collaborator),
            'message' => 'Collaborator retrieved successfully',
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Collaborator $collaborator)
    {
        $collaborator->delete();

        return response()->json([
            'message' => 'Collaborator deleted successfully',
        ], Response::HTTP_NO_CONTENT);
    }
}
