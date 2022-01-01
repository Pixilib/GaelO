<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\GetCountry\GetCountry;
use App\GaelO\UseCases\GetCountry\GetCountryRequest;
use App\GaelO\UseCases\GetCountry\GetCountryResponse;
use Illuminate\Support\Facades\Auth;

class CountryController extends Controller
{
    public function getCountry(?string $code = null, GetCountryRequest $getCountryRequest, GetCountryResponse $getCountryResponse, GetCountry $getCountry)
    {
        $currentUser = Auth::user();
        $getCountryRequest->currentUserId = $currentUser['id'];
        $getCountryRequest->code = $code;
        $getCountry->execute($getCountryRequest, $getCountryResponse);
        return $this->getJsonResponse($getCountryResponse->body, $getCountryResponse->status, $getCountryResponse->statusText);
    }
}
