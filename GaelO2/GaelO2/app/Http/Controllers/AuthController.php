<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function login(Request $request){
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string'
        ]);

        $credentials = request(['username', 'password']);

        /*if(!Auth::attempt($credentials)) {
            return response()->json('Unauthorized', 401);
        }*/
        //$request->user();
        $user = User::where('username', $request->username)->first();

        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;

        $token->expires_at = Carbon::now()->addHour();
        $token->save();

        return response()->json([
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse($tokenResult->token->expires_at)->toDateTimeString()
        ], 200);
    }

    public function logout(Request $request) {
        $request->user()->token()->revoke();
        return response();
    }
}
