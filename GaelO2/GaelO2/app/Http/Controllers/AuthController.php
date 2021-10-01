<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\Login\Login;
use App\GaelO\UseCases\Login\LoginRequest;
use App\GaelO\UseCases\Login\LoginResponse;
use App\GaelO\Util;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(Request $request, Login $login, LoginRequest $loginRequest, LoginResponse $loginResponse)
    {

        $requestData = $request->all();
        $loginRequest = Util::fillObject($requestData, $loginRequest);
        $loginRequest->ip = $request->ip();

        $login->execute($loginRequest, $loginResponse);

        if ($loginResponse->status === 200) {

            $user = User::where('username', $request->username)->sole();
            $tokenResult = $user->createToken('GaelO');

            return response()->json([
                'id' => $user->id,
                'access_token' => $tokenResult->plainTextToken,
                'token_type' => 'Bearer'
            ], 200);
        } else {
            return $this->getJsonResponse($loginResponse->body, $loginResponse->status, $loginResponse->statusText);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json();
    }
}
