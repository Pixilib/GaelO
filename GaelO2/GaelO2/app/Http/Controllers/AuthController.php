<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\CreateMagicLink\CreateMagicLink;
use App\GaelO\UseCases\CreateMagicLink\CreateMagicLinkRequest;
use App\GaelO\UseCases\CreateMagicLink\CreateMagicLinkResponse;
use App\GaelO\UseCases\Login\Login;
use App\GaelO\UseCases\Login\LoginRequest;
use App\GaelO\UseCases\Login\LoginResponse;
use App\GaelO\Util;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Routing\UrlGenerator;

class AuthController extends Controller
{
    public function login(Request $request, Login $login, LoginRequest $loginRequest, LoginResponse $loginResponse)
    {

        $requestData = $request->all();
        $loginRequest = Util::fillObject($requestData, $loginRequest);
        $loginRequest->ip = $request->ip();

        $login->execute($loginRequest, $loginResponse);

        if ($loginResponse->status === 200) {

            $user = User::where('email', $request->email)->sole();

            //remove all tokens of current user before creating one other
            $user->tokens()->delete();

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
        $request->user()->currentAccessToken()->delete();
        return response()->json();
    }

    public function getMagicLink(Request $request, UrlGenerator $urlGenerator){

            if (!$request->hasValidSignature() || !$urlGenerator->hasCorrectSignature($request) || !$urlGenerator->signatureHasNotExpired($request)) {
                throw new AuthorizationException();
            }

            $user = User::findOrFail($request->id);

            return response()->json([
                'token' => $user->createToken('api')->plainTextToken,
                'userId' => $user->id,
                'user' => $user,
                'redirect_to' => $request->redirect_to
            ], 200);

    }

    public function createMagicLink(int $userId, Request $request, CreateMagicLink $createMagicLink, CreateMagicLinkRequest $createMagicLinkRequest, CreateMagicLinkResponse $createMagicLinkResponse) {

        $curentUser = $request->user();
        $requestData = $request->all();

        $createMagicLinkRequest->targetUser = $userId;
        $createMagicLinkRequest->currentUserId = $curentUser['id'];

        $createMagicLinkRequest = Util::fillObject($requestData, $createMagicLinkRequest);

        $createMagicLink->execute($createMagicLinkRequest, $createMagicLinkResponse);

        return $this->getJsonResponse($createMagicLinkResponse->body, $createMagicLinkResponse->status, $createMagicLinkResponse->statusText);

    }

}
