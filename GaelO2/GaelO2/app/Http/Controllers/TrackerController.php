<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\GetTracker\GetTracker;
use App\GaelO\UseCases\GetTracker\GetTrackerRequest;
use App\GaelO\UseCases\GetTracker\GetTrackerResponse;
use App\GaelO\UseCases\GetTracker\GetStudyTracker;
use App\GaelO\UseCases\GetTracker\GetStudyTrackerRequest;
use App\GaelO\UseCases\GetTracker\GetStudyTrackerResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrackerController extends Controller
{
    public function getTracker(Request $request, GetTrackerRequest $getTrackerRequest, GetTrackerResponse $getTrackerResponse, GetTracker $getTracker) {
        $currentUser = Auth::user();
        $getTrackerRequest->currentUserId = $currentUser['id'];
        $queryParam = $request->query();
        $getTrackerRequest->admin = $queryParam['admin'];
        $getTracker->execute($getTrackerRequest, $getTrackerResponse);
        return response()->json($getTrackerResponse->body)
            ->setStatusCode($getTrackerResponse->status, $getTrackerResponse->statusText);
    }

    public function getStudyTracker(Request $request, getStudyTrackerRequest $getStudyTrackerRequest, getStudyTrackerResponse $getStudyTrackerResponse, getStudyTracker $getStudyTracker) {
        $currentUser = Auth::user();
        $getStudyTrackerRequest->currentUserId = $currentUser['id'];
        $queryParam = $request->query();
        $getStudyTrackerRequest->requiredTracker = $queryParam['tracker'];
        $getStudyTracker->execute($getStudyTrackerRequest, $getStudyTrackerResponse);
        return response()->json($getStudyTrackerResponse->body)
            ->setStatusCode($getStudyTrackerResponse->status, $getStudyTrackerResponse->statusText);
    }
    
}