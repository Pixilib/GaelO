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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Routing\UrlGenerator;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class AuthController extends Controller
{
    public function login(Request $request, Login $login, LoginRequest $loginRequest, LoginResponse $loginResponse)
    {

        $requestData = $request->all();
        Util::fillObject($requestData, $loginRequest);
        $loginRequest->ip = $request->ip();

        $login->execute($loginRequest, $loginResponse);

        if ($loginResponse->status === 200) {
            $userId = $loginResponse->userId;
            $use2FA = $loginResponse->use2FA;

            if ($use2FA) {
                // Temporary Opaque Token. available for 5 minutes
                $challengeToken = Str::uuid()->toString();
                Cache::put('2fa_challenge_' . $challengeToken, $userId, now()->addMinutes(5));

                return response()->json([
                    'onboarded' => $loginResponse->onboarded,
                    'needs2FA' => true,
                    'challenge_token' => $challengeToken
                ], 200);
            }


            // Regular Login
            $user = User::where('email', strtolower($request->email))->sole();
            $isAdmin = $user->administrator;

            if ($isAdmin && !$use2FA) {
                return response()->json([
                    'id' => $user->id,
                    //'onboarded' => $loginResponse->onboarded,
                    'needs2FA' => true
                    
                ]);
            }

            $tokenResult = $user->createToken('GaelO');

            return response()->json([
                'id' => $user->id,
                'onboarded' => $loginResponse->onboarded,
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

    public function twoFactorChallenge(
        Request $request,
        TwoFactorAuthenticationProvider $provider
    ): JsonResponse {

        $challengeToken = $request->input('challenge_token');

        if (!$challengeToken) {
            return response()->json(['message' => 'Session expired.'], 422);
        }

        // pull = get + delete in one operaion (single use)
        $userId = Cache::pull('2fa_challenge_' . $challengeToken);

        if (!$userId) {
            return response()->json(['message' => 'Session expired or already use.'], 422);
        }

        $user = User::findOrFail($userId);

        $code = $request->input('code');
        $recoveryCode = $request->input('recovery_code');

        $valid = false;

        if ($recoveryCode) {
            $codes = json_decode(decrypt($user->two_factor_recovery_codes), true);
            $index = array_search($recoveryCode, $codes);

            if ($index !== false) {
                array_splice($codes, $index, 1);
                $user->forceFill([
                    'two_factor_recovery_codes' => encrypt(json_encode($codes))
                ])->save();
                $valid = true;
            }

        } elseif ($code) {
            $valid = $provider->verify(
                decrypt($user->two_factor_secret),
                $code
            );
        }

        if (!$valid) {
            return response()->json([
                'errors' => ['code' => ['invalid code']]
            ], 422);
        }

        $tokenResult = $user->createToken('GaelO');

        return response()->json([
            'id' => $user->id,
            'access_token' => $tokenResult->plainTextToken,
            'token_type' => 'Bearer'
        ], 200);
    }

    /**
     * Generate 2FA secret + QR code SVG for the authenticated user.
     * Route protected by auth:sanctum — the Bearer token from login
     * is sent explicitly by the front before being stored in Redux.
     * Must be in the auth:sanctum group but NOT in the onboarded group
     * (admin may not be onboarded yet when setting up 2FA).
     */
    public function setup2FA(Request $request, TwoFactorAuthenticationProvider $provider): JsonResponse
    {
        $user = $request->user();

        // Generate secret if not already set
        if (empty($user->two_factor_secret)) {
            $user->forceFill([
                'two_factor_secret' => encrypt($provider->generateSecretKey()),
                'two_factor_recovery_codes' => encrypt(json_encode(
                    collect(range(1, 8))->map(fn() => Str::random(10) . '-' . Str::random(10))->all()
                ))
            ])->save();
        }

        $appName = urlencode(config('app.name'));
        $email = urlencode($user->email);
        $secret = decrypt($user->two_factor_secret);
        $otpauthUrl = "otpauth://totp/{$appName}:{$email}?secret={$secret}&issuer={$appName}";

        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(192),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );
        $svg = (new \BaconQrCode\Writer($renderer))->writeString($otpauthUrl);

        return response()->json(['svg' => $svg]);
    }

    /**
     * Confirm 2FA setup by verifying the TOTP code for the authenticated user.
     */
    public function confirmSetup2FA(Request $request, TwoFactorAuthenticationProvider $provider): JsonResponse
    {
        $user = $request->user();

        if (empty($user->two_factor_secret)) {
            return response()->json(['message' => '2FA not initialized'], 422);
        }

        $valid = $provider->verify(decrypt($user->two_factor_secret), $request->input('code'));

        if (!$valid) {
            return response()->json(['errors' => ['code' => ['invalid code']]], 422);
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return response()->json(['message' => '2FA activated']);
    }


    public function generateRecoveryCodes2FA(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (empty($user->two_factor_secret) || empty($user->two_factor_confirmed_at)) {
            return response()->json(['message' => '2FA Not activated'], 422);
        }

        // Decrypt to return the code on the front side
        $codes = $user->recoveryCodes();
        foreach ($codes as $code) {
            $user->replaceRecoveryCode($code);
        }

        $updatedCodes = $user->recoveryCodes();
        return response()->json(['recoveryCodes' => $updatedCodes]);
    }

    public function getMagicLink(Request $request, UrlGenerator $urlGenerator)
    {

        if (!$request->hasValidSignature() || !$urlGenerator->hasCorrectSignature($request) || !$urlGenerator->signatureHasNotExpired($request)) {
            throw new AuthorizationException();
        }

        $user = User::findOrFail($request->id);
        $token = $user->createToken('GaelO')->plainTextToken;

        $requestQueryParams = $request->query_params;
        $requestQueryParams['userId'] = $request->id;
        $requestQueryParams['token'] = $token;

        return response()->redirectTo($request->redirect_to . '?' . http_build_query($requestQueryParams));
    }

    public function createMagicLink(Request $request, CreateMagicLink $createMagicLink, CreateMagicLinkRequest $createMagicLinkRequest, CreateMagicLinkResponse $createMagicLinkResponse, int $userId)
    {

        $currentUser = $request->user();
        $requestData = $request->all();

        Util::fillObject($requestData, $createMagicLinkRequest);
        $createMagicLinkRequest->targetUser = $userId;
        $createMagicLinkRequest->currentUserId = $currentUser['id'];

        $createMagicLink->execute($createMagicLinkRequest, $createMagicLinkResponse);

        return $this->getJsonResponse($createMagicLinkResponse->body, $createMagicLinkResponse->status, $createMagicLinkResponse->statusText);
    }

    public function getSystem(Request $request, GetSystem $getSystem, GetSystemRequest $getSystemRequest, GetSystemResponse $getSystemResponse)
    {
        $currentUser = $request->user();
        $getSystemRequest->currentUserId = $currentUser['id'];
        $getSystem->execute($getSystemRequest, $getSystemResponse);
        return $this->getJsonResponse($getSystemResponse->body, $getSystemResponse->status, $getSystemResponse->statusText);
    }

    
}
