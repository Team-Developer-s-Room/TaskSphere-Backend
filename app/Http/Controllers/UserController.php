<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\Auth\UserResource;
use Illuminate\Http\Response;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::all();

        return response()->json([
            'data' => UserResource::collection($users),
            'message' => 'Users retrieved successfully',
        ], Response::HTTP_OK);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return response()->json([
            'data' => new UserResource($user),
            'message' => 'User retrieved successfully',
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();

        $user->update($validated);

        return response()->json([
            'data' => new UserResource($user),
            'message' => 'Data updated successfully',
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        // TODO
        // Soft delete the user
        // Do something regarding the projects he created
        // Do something regarding the projects he is collaborating in
    }
}
