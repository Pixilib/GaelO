<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\GetPreference\GetPreferences;
use App\GaelO\UseCases\GetPreference\GetPreferencesResponse;
use App\GaelO\UseCases\GetPreference\GetPreferencesRequest;
use Illuminate\Http\Request;

class PreferenceController extends Controller
{
    public function getPreference(GetPreferences $getPreferences, GetPreferencesRequest $getPreferencesRequest, GetPreferencesResponse $getPreferencesResponse){

        $getPreferences->execute($getPreferencesRequest, $getPreferencesResponse);

        return response()->json($getPreferencesResponse->body)
                ->setStatusCode($getPreferencesResponse->status, $getPreferencesResponse->statusText);

    }
}
