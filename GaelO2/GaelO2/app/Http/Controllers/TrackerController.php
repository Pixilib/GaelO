<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\GetTracker\GetTracker;
use App\GaelO\UseCases\GetTracker\GetTrackerRequest;
use App\GaelO\UseCases\GetTracker\GetTrackerResponse;
use Illuminate\Http\Request;

class TrackerController extends Controller
{
    public function getTracker(Request $request, GetTrackerRequest $getTrackerRequest, GetTrackerResponse $getTrackerResponse, GetTracker $getTracker) {
        $queryParam = $request->query();
        $getTrackerRequest->admin = $queryParam['admin'];
        $getTracker->execute($getTrackerRequest, $getTrackerResponse);
        return response()->json($getTrackerResponse->body)
            ->setStatusCode($getTrackerResponse->status, $getTrackerResponse->statusText);
    }
}
