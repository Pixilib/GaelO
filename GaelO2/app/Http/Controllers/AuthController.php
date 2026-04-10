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
use Exception;

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

            // Regular Login
            $user = User::findOrFail($userId);

            if ($use2FA) {
                // Temporary Opaque Token. available for 5 minutes
                $challengeToken = Str::uuid()->toString();
                Cache::put('2fa_challenge_' . $challengeToken, $userId, now()->addMinutes(5));

                return response()->json([
                    'id' => $user->id,
                    'onboarded' => $loginResponse->onboarded,
                    'needs2FA' => true,
                    'challenge_token' => $challengeToken
                ], 200);
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

        $userId = Cache::get('2fa_challenge_' . $challengeToken);

        if (!$userId) {
            return response()->json(['message' => 'Session expired or already used.'], 422);
        }

        $user = User::findOrFail($userId);

        $code = $request->input('code');

        $valid = false;

        try {
            $secret = decrypt($user->two_factor_secret);
        } catch (\Throwable $e) {
            return response()->json([
                'errors' => ['code' => ['Invalid 2FA configuration']]
            ], 422);
        }

        $valid = $provider->verify($secret, $code);

        if (!$valid) {
            return response()->json([
                'errors' => ['code' => ['invalid code']]
            ], 422);
        }


        $tokenResult = $user->createToken('GaelO');
        Cache::forget('2fa_challenge_' . $challengeToken);
        return response()->json([
            'id' => $user->id,
            'access_token' => $tokenResult->plainTextToken,
            'token_type' => 'Bearer'
        ], 200);
    }

    public function recoveryCodeChallenge(
        Request $request
    ): JsonResponse {

        $challengeToken = $request->input('challenge_token');

        if (!$challengeToken) {
            return response()->json(['message' => 'Session expired.'], 422);
        }

        $userId = Cache::get('2fa_challenge_' . $challengeToken);

        if (!$userId) {
            return response()->json(['message' => 'Session expired or already used.'], 422);
        }

        $user = User::findOrFail($userId);

        $recoveryCode = $request->input('recovery_code');

        $valid = false;

        if (empty($user->two_factor_recovery_codes)) {
            return response()->json([
                'errors' => ['recovery_code' => ['No recovery codes available']]
            ], 422);
        }

        try {
            $decrypted = decrypt($user->two_factor_recovery_codes);
            $codes = json_decode($decrypted, true);

            if (!is_array($codes)) {
                throw new \Exception();
            }

        } catch (\Throwable $e) {
            return response()->json([
                'errors' => ['recovery_code' => ['Invalid recovery codes']]
            ], 422);
        }
        $index = array_search($recoveryCode, $codes);

        if ($index !== false) {
            array_splice($codes, $index, 1);
            $user->forceFill([
                'two_factor_recovery_codes' => encrypt(json_encode($codes))
            ])->save();
            $valid = true;
        }

        if (!$valid) {
            return response()->json([
                'errors' => ['recovery_code' => ['invalid code']]
            ], 422);
        }

        $tokenResult = $user->createToken('GaelO');
        Cache::forget('2fa_challenge_' . $challengeToken);
        return response()->json([
            'id' => $user->id,
            'access_token' => $tokenResult->plainTextToken,
            'token_type' => 'Bearer'
        ], 200);
    }

    /**
     * Generate 2FA secret + QR code SVG for the authenticated user.
     */
    public function setup2FA(Request $request, TwoFactorAuthenticationProvider $provider, int $userId): JsonResponse
    {
        $user = $request->user();

        if ($user->id !== $userId) {
            abort(403, "Can't activate 2FA for a third party account");
        }

        // Generate secret if not already set
        if (empty($user->two_factor_secret)) {
            $user->forceFill([
                'two_factor_secret' => encrypt($provider->generateSecretKey())
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
    public function confirmSetup2FA(Request $request, TwoFactorAuthenticationProvider $provider, int $userId): JsonResponse
    {
        $user = $request->user();

        if ($user->id !== $userId) {
            abort(403, "Can't activate 2FA for a third party account");
        }

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


    public function generateRecoveryCodes2FA(Request $request, int $userId): JsonResponse
    {
        $user = $request->user();

        if ($user->id !== $userId) {
            abort(403, "Can't generate recovery codes for a thrid party account");
        }

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (empty($user->two_factor_secret) || empty($user->two_factor_confirmed_at)) {
            return response()->json(['message' => '2FA Not activated'], 422);
        }

        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = Str::random(10) . '-' . Str::random(10);
        }

        $user->forceFill([
            'two_factor_recovery_codes' => encrypt(json_encode($codes))
        ])->save();

        // Decrypt to return the code on the front side
        $codes = $user->recoveryCodes();
        return response()->json(['recoveryCodes' => $codes]);
    }

    public function delete2FA(Request $request, int $userId)
    {
        $authUser = $request->user();
        $user = User::findOrFail($userId);

        if (!$authUser->administrator && $authUser->id !== $userId) {
            abort(403, "You cannot delete 2FA for another user");
        }

        // Is 2FA activated
        if (empty($user->two_factor_secret)) {
            return response()->json(['message' => '2FA not initialized'], 422);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null
        ])->save();

        return response()->json(['message' => '2FA Deleted']);
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
