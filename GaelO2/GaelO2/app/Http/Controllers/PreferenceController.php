<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\GetPreferences\GetPreferences;
use App\GaelO\UseCases\GetPreferences\GetPreferencesResponse;
use App\GaelO\UseCases\GetPreferences\GetPreferencesRequest;
use Illuminate\Http\Request;

class PreferenceController extends Controller
{
    public function getPreference(GetPreferences $getPreferences, GetPreferencesRequest $getPreferencesRequest, GetPreferencesResponse $getPreferencesResponse){

        $getPreferences->execute($getPreferencesRequest, $getPreferencesResponse);

        return response()->json($getPreferencesResponse->body)
                ->setStatusCode($getPreferencesResponse->status, $getPreferencesResponse->statusText);

    }
}
