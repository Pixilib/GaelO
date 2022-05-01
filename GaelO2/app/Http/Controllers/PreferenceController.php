<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\GetPreference\GetPreferences;
use App\GaelO\UseCases\GetPreference\GetPreferencesResponse;
use App\GaelO\UseCases\GetPreference\GetPreferencesRequest;
use Illuminate\Support\Facades\Auth;

class PreferenceController extends Controller
{
    public function getPreference(GetPreferences $getPreferences, GetPreferencesRequest $getPreferencesRequest, GetPreferencesResponse $getPreferencesResponse){

        $currentUser = Auth::user();
        $getPreferencesRequest->currentUserId = $currentUser['id'];
        $getPreferences->execute($getPreferencesRequest, $getPreferencesResponse);

        return $this->getJsonResponse($getPreferencesResponse->body, $getPreferencesResponse->status, $getPreferencesResponse->statusText);

    }
}
