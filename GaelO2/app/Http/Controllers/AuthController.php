<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\CreateMagicLink\CreateMagicLink;
use App\GaelO\UseCases\CreateMagicLink\CreateMagicLinkRequest;
use App\GaelO\UseCases\CreateMagicLink\CreateMagicLinkResponse;
use App\GaelO\UseCases\GetSystem\GetSystem;
use App\GaelO\UseCases\GetSystem\GetSystemRequest;
use App\GaelO\UseCases\GetSystem\GetSystemResponse;
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
            $token = $user->createToken('GaelO')->plainTextToken;

            return response()->redirectTo($request->redirect_to."?userId=".$request->id."&token=".$token);

    }

    public function createMagicLink(Request $request, CreateMagicLink $createMagicLink, CreateMagicLinkRequest $createMagicLinkRequest, CreateMagicLinkResponse $createMagicLinkResponse, int $userId) {

        $currentUser = $request->user();
        $requestData = $request->all();

        $createMagicLinkRequest->targetUser = $userId;
        $createMagicLinkRequest->currentUserId = $currentUser['id'];

        $createMagicLinkRequest = Util::fillObject($requestData, $createMagicLinkRequest);

        $createMagicLink->execute($createMagicLinkRequest, $createMagicLinkResponse);

        return $this->getJsonResponse($createMagicLinkResponse->body, $createMagicLinkResponse->status, $createMagicLinkResponse->statusText);

    }

    public function getSystem(Request $request, GetSystem $getSystem, GetSystemRequest $getSystemRequest, GetSystemResponse $getSystemResponse){
        $currentUser = $request->user();
        $getSystemRequest->currentUserId = $currentUser['id'];
        $getSystem->execute($getSystemRequest, $getSystemResponse);
        return $this->getJsonResponse($getSystemResponse->body, $getSystemResponse->status, $getSystemResponse->statusText);
    }

}
