<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\GetTrackerAdmin\GetTrackerAdmin;
use App\GaelO\UseCases\GetTrackerAdmin\GetTrackerAdminRequest;
use App\GaelO\UseCases\GetTrackerAdmin\GetTrackerAdminResponse;
use App\GaelO\UseCases\GetTrackerUser\GetTrackerUser;
use App\GaelO\UseCases\GetTrackerUser\GetTrackerUserRequest;
use App\GaelO\UseCases\GetTrackerUser\GetTrackerUserResponse;
use Illuminate\Http\Request;


class TrackerController extends Controller
{
    public function getTracker(Request $request, GetTrackerAdminRequest $getTrackerAdminRequest, GetTrackerAdminResponse $getTrackerAdminResponse, GetTrackerAdmin $getTrackerAdmin, GetTrackerUserRequest $getTrackerUserRequest, GetTrackerUserResponse $getTrackerUserResponse, GetTrackerUser $getTrackerUser) {
        $queryParam = $request->query();
        if(array_key_exists('true', $queryParam) ){
            $getTrackerAdmin->execute($getTrackerAdminRequest, $getTrackerAdminResponse);
            return response()->json($getTrackerAdminResponse->body)
                    ->setStatusCode($getTrackerAdminResponse->status, $getTrackerAdminResponse->statusText);
        } else {
            $getTrackerUser->execute($getTrackerUserRequest, $getTrackerUserResponse);
            return response()->json($getTrackerUserResponse->body)
                    ->setStatusCode($getTrackerUserResponse->status, $getTrackerUserResponse->statusText);

        }
    }
}
