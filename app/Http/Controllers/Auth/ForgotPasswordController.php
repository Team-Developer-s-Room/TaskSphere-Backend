<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class ForgotPasswordController extends Controller
{
    public function sendResetLinkEmail(){
        $validator = Validator::make(request()->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $response = Password::sendResetLink(
            request()->only('email')
        );

        if($response == Password::RESET_LINK_SENT){
            return response()->json(['message' => 'Reset link sent to your email'], 200);
        }else{
            return response()->json(['message' => 'Failed to send reset link'], 500);
        }
    }
}
