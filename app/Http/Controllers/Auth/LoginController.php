<?php

namespace App\Http\Controllers\Auth;

use App\Http\Resources\Auth\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class LoginController extends Controller
{
    public function login()
    {
        $validated = request()->validate([
            'email' => "required|email|exists:user,email",
            "password" > "required"
        ]);

        if (auth()->attempt($validated)) {
            $user = auth()->user();
            $token = $user->createToken('API Token')->plainTextToken;

            return response()->json([
                'data' => new UserResource($user),
                'token' => $token,
                'message' => 'User registered successfully',
            ], Response::HTTP_OK);
        }
    }
}
