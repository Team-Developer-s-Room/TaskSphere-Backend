<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\Auth\UserResource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

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
        Gate::authorize('update');

        $validated = $request->validated();

        if($request->hasFile('image')) {
            if($user->image && Storage::disk('public')->exists($user->image)) {
                Storage::disk('public')->delete($user->image);
            }

            $imagePath = $request->file('image')->store('profile-images', 'public');
            $validated['image'] = $imagePath;
        }

        $user->update($validated);

        return response()->json([
            'data' => new UserResource($user),
            'message' => 'User updated successfully',
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        Gate::authorize('delete');
        // TODO
        // Soft delete the user
        // Do something regarding the projects he created
        // Do something regarding the projects he is collaborating in
    }
}
