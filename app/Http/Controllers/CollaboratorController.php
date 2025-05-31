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

use function Illuminate\Support\defer;

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
    // public function invite(StoreCollaboratorRequest $request, Project $project)
    // {
    //     Gate::authorize('create', $project);

    //     $validated = $request->validated();
    //     $user = User::where('email', $validated['email'])->firstOrFail();

    //     $invite_url = URL::temporarySignedRoute(
    //         'collaborators.store',
    //         now()->addDays(7),
    //         ['user' => $user->nano_id, 'project' => $project->nano_id]
    //     );

    //     defer(fn() => $user->notify(new CollaborationInvite(
    //         $project->name,
    //         $invite_url
    //     )));

    //     return response()->json([
    //         'message' => 'Collaborator invite sent successfully',
    //     ], Response::HTTP_OK);
    // }

    public function invite(StoreCollaboratorRequest $request, Project $project)
    {
        Gate::authorize('create', $project);

        $validated = $request->validated();
        $emails = $validated['emails'];

        // Determine the frontend base URL based on request scheme
        $isSecure = $request->isSecure(); // returns true for HTTPS, false for HTTP
        $frontendBaseUrl = $isSecure
            ? 'https://task-sphere-five.vercel.app/app?modalIndicator=true&inviteLink='
            : 'http://localhost:5173/app?modalIndicator=true&inviteLink=';

        foreach ($emails as $email) {
            $user = User::where('email', $email)->first();

            if (!$user) {
                continue; // Skip if user somehow isn't found, though validation should catch this
            }

            // Generate the temporary signed backend URL
            $backendInviteUrl = URL::temporarySignedRoute(
                'collaborators.store',
                now()->addDays(7),
                ['user' => $user->nano_id, 'project' => $project->nano_id]
            );

            // Extract only the path and query
            $parsedUrl = parse_url($backendInviteUrl);
            $path = $parsedUrl['path'] ?? '';
            $query = isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '';
            $invitePath = ltrim($path . $query, '/');

            // Construct frontend invite URL
            $frontendUrl = $frontendBaseUrl . urlencode($invitePath);

            // Defer notification
            defer(fn() => $user->notify(new CollaborationInvite(
                $project->name,
                $frontendUrl
            )));
        }

        return response()->json([
            'message' => 'Collaborator invites sent successfully',
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Project $project, User $user)
    {
        // Check for valid signed route
        if (! $request->hasValidSignature()) {
            return response()->json([
                'message' => 'Invalid or expired invitation link',
            ], Response::HTTP_FORBIDDEN);
        }
    
        // Check if user is already a collaborator
        if (Collaborator::where('user_id', $user->id)->where('project_id', $project->id)->exists()) {
            return response()->json([
                'message' => 'User is already a collaborator on this project.',
            ], Response::HTTP_CONFLICT);
        }
    
        // Create the collaborator record
        $collaborator = Collaborator::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
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
            'message' => 'Collaborator removed successfully',
        ], Response::HTTP_OK);
    }

}
