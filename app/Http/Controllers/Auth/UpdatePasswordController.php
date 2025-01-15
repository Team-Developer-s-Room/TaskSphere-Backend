<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class UpdatePasswordController   extends Controller
{
    public function updatePassword(UpdatePasswordRequest $request)
    {
        $user = auth()->user();

        // Check if the current password is correct
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        
        // Update the password
        $user->update([
            'new_password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Password updated successfully',
        ], Response::HTTP_OK);
    }
}
