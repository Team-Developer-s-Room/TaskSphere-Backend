<?php

namespace App\Http\Controllers;

use App\Http\Requests\RemoveTaskUserRequest;
use App\Models\TaskUser;
use App\Http\Requests\UpdateTaskUserRequest;
use App\Http\Resources\Auth\UserResource;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssigned;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

use function Illuminate\Support\defer;

class TaskUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Task $task)
    {
        $users = $task->users;

        return response()->json([
            'data' => UserResource::collection($users),
            'message' => 'Assignees retrieved successfully',
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Task $task, User $user)
    {
        $task->load('project');
        Gate::authorize('assignUser', $task);

        $project = $task->project;

        $isAllowed =
            $project->collaborators()->where('user_id', $user->id)->exists() ||
            $project->admin_id === $user->id;

        if (! $isAllowed) {
            return response()->json([
                'message' => 'The selected user must be a collaborator or the project admin.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($task->users()->where('users.id', $user->id)->exists()) {
            return response()->json([
                'message' => 'User already assigned to this task.',
            ], Response::HTTP_CONFLICT);
        }

        $taskUser = TaskUser::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
        ]);

        defer(fn() => $user->notify(new TaskAssigned(
            $project->name,
            'Notification_url'
        )));

        return response()->json([
            'data' => new UserResource($user),
            'message' => 'Assignee added successfully',
        ], Response::HTTP_CREATED);
    }

    // /**
    //  * Display the specified resource.
    //  */
    // public function show(TaskUser $taskUser)
    // {
    //     $user = $taskUser->user;

    //     return response()->json([
    //         'data' => new UserResource($user),
    //         'message' => 'Assignee retrieved successfully',
    //     ], Response::HTTP_OK);
    // }

    // /**
    //  * Update the specified resource in storage.
    //  */
    // public function update(UpdateTaskUserRequest $request, TaskUser $taskUser)
    // {
    //     $validated = $request->validated();
        
    //     $taskUser->update($validated);
    //     $user = $taskUser->user;

    //     return response()->json([
    //         'data' => new UserResource($user),
    //         'message' => 'Assignee updated successfully',
    //     ], Response::HTTP_OK);
    // }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task, User $user)
    {
        $task->load('project');
        Gate::authorize('delete', $task);

        TaskUser::where('task_id', $task->id)
        ->where('user_id', $user->id)
        ->delete();

        return response()->json([
            'message' => 'Assignee removed successfully',
        ], Response::HTTP_OK);
    }
}
