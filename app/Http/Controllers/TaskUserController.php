<?php

namespace App\Http\Controllers;

use App\Http\Requests\RemoveTaskUserRequest;
use App\Models\TaskUser;
use App\Http\Requests\StoreTaskUserRequest;
use App\Http\Requests\UpdateTaskUserRequest;
use App\Http\Resources\Auth\UserResource;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssigned;
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
    public function store(StoreTaskUserRequest $request, Task $task)
    {
        $task->load('project');
        Gate::authorize('create', $task);

        $validated = $request->validated();
        $validated['task_id'] = $task->id;

        $taskUser = TaskUser::create($validated);
        $user = $taskUser->user;

        defer(fn() => $user->notify(new TaskAssigned(
            $task->project->name,
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
            'message' => 'Assignee deleted successfully',
        ], Response::HTTP_OK);
    }
}
