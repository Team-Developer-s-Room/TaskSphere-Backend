<?php

namespace App\Http\Controllers\Auth;

use App\Http\Resources\Auth\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function login()
    {
        $validated = request()->validate([
            'email' => "required|email|exists:user,email",
            "password" > "required"
        ]);

        if (!Auth::attempt($validated)) {
            abort(response()->json([
                'message' => 'Invalid credentials',
            ], Response::HTTP_UNAUTHORIZED));
        }

        $user = Auth::user();
        $token = $user->createToken('API Token')->plainTextToken;

        return response()->json([
            'data' => new UserResource($user),
            'token' => $token,
            'message' => 'User logged in successfully',
        ], Response::HTTP_OK);
    }

    public function logout(Request $request)
    {
        // $request->user()->tokens()->delete();
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'User logged out successfully',
        ], Response::HTTP_OK);
    }
}
