<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use Laravel\Sanctum\PersonalAccessToken;

class AccessTokensController extends Controller
{
    // store token in database with user data
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:6',
            'device_name' => 'string|max:255',
            'abilities' => 'nullable|array'
        ]);

        // get user data by email
        $user = User::where('email', $request->email)->first();
        // check data if has user and password
        if ($user && Hash::check($request->password, $user->password)) {
            
            // create token
            // we use device_name to help us to create token
            $device_name = $request->post('device_name', $request->userAgent());
            $token = $user->createToken($device_name, $request->post('abilities'));

            // we use ::json with API
            // return token
            return Response::json([
                'code' => 1,
                'token' => $token->plainTextToken,
                'user' => $user,
            ], 201);

        }

        // we use ::json with API
        // return error
        return Response::json([
            'code' => 0,
            'message' => 'Invalid credentials',
        ], 401);
        
    }

    public function destroy($token = null)
    {
        $user = Auth::guard('sanctum')->user();


        // Revoke all tokens
        // $user->tokens()->delete();

        if (null === $token) {
            // $user->currentAccessToken()->delete();
            return;
        }

        // get token from database
        $personalAccessToken = PersonalAccessToken::findToken($token);
        // check if this token regards to this user
        if (
            $user->id == $personalAccessToken->tokenable_id 
            && get_class($user) == $personalAccessToken->tokenable_type
        ) {
            $personalAccessToken->delete();
        }
    }
}
