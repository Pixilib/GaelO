<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\GetPreference\GetPreferences;
use App\GaelO\UseCases\GetPreference\GetPreferencesResponse;
use App\GaelO\UseCases\GetPreference\GetPreferencesRequest;
use App\GaelO\UseCases\ModifyPreference\ModifyPreference;
use App\GaelO\UseCases\ModifyPreference\ModifyPreferenceRequest;
use App\GaelO\UseCases\ModifyPreference\ModifyPreferenceResponse;
use App\GaelO\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PreferenceController extends Controller
{
    public function getPreference(GetPreferences $getPreferences, GetPreferencesRequest $getPreferencesRequest, GetPreferencesResponse $getPreferencesResponse){

        $getPreferences->execute($getPreferencesRequest, $getPreferencesResponse);

        return response()->json($getPreferencesResponse->body)
                ->setStatusCode($getPreferencesResponse->status, $getPreferencesResponse->statusText);

    }

    public function modifyPreference(Request $request, ModifyPreference $modifyPreference, ModifyPreferenceRequest $modifyPreferenceRequest, ModifyPreferenceResponse $modifyPreferenceResponse){

        $currentUser = Auth::user();
        $modifyPreferenceRequest->currentUserId = $currentUser['id'];
        $requestData = $request->all();
        $modifyPreferenceRequest = Util::fillObject($requestData, $modifyPreferenceRequest);

        $modifyPreference->execute($modifyPreferenceRequest, $modifyPreferenceResponse);
        return response()->noContent()
                ->setStatusCode($modifyPreferenceResponse->status, $modifyPreferenceResponse->statusText);
    }
}
