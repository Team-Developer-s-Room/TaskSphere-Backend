<?php

namespace App\Http\Controllers;

use App\Models\Collaborator;
use App\Http\Requests\StoreCollaboratorRequest;
use App\Http\Requests\UpdateCollaboratorRequest;
use App\Http\Resources\Auth\UserResource;
use App\Models\Project;
use App\Models\User;
use App\Notifications\CollaborationInvite;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

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
     * Send coolaboration request to user
     */
    public function invite(StoreCollaboratorRequest $request, Project $project)
    {
        Gate::authorize('create', $project);

        $validated = $request->validated();

        $signed_url = URL::temporarySignedRoute(
            'collaborators.store', now()->addDays(7),
            ['user' => $validated['user_id'], 'project' => $project->nano_id]
        );

        $user = User::where('nano_id', $validated['user_id'])->firstOrFail();
        $user->notify(new CollaborationInvite(
            $project->name,
            $user->username,
            $signed_url,
            'Notification_url'
        ));

        return response()->json([
            'message' => 'Collaborator invite sent successfully',
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, User $user, Project $project)
    {
        // Check for valid signed route
        if ($request->hasValidSignature()) {
            return response()->json([
                'message' => 'Invalid or expired invitation link',
            ], Response::HTTP_FORBIDDEN);
        }

        // Check if the user has already accepted the invitation
        if (Collaborator::where('user_id', $user->id)->where('project_id', $project->id)->exists()) {
            return response()->json([
                'message' => 'User is already a collaborator on this project.',
            ], Response::HTTP_CONFLICT);
        }

        $collaborator = Collaborator::create([
            'user_id' => $user->nano_id,
            'project_id' => $project->nano_id,
        ]);

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
    public function destroy(Project $project, User $user)
    {
        Gate::authorize('delete', $project);

        Collaborator::where('project_id', $project->id)
        ->where('user_id', $user->id)
        ->delete();

        return response()->json([
            'message' => 'Collaborator deleted successfully',
        ], Response::HTTP_OK);
    }

}
