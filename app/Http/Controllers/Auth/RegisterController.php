<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\Auth\UserResource;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
   public function register(RegisterRequest $request)
   {
       $user = User::create([
           'username' => $request->username,
           'email' => $request->email,
           'password' => Hash::make($request->password),
       ]);

       $token = $user->createToken('API Token')->plainTextToken;

       return response()->json([
           'data' => new UserResource($user),
           'token' => $token,
           'message' => 'User registered successfully',
       ], Response::HTTP_CREATED);
   }
}
