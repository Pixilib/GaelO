<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\GetTracker\GetTracker;
use App\GaelO\UseCases\GetTracker\GetTrackerRequest;
use App\GaelO\UseCases\GetTracker\GetTrackerResponse;
use App\GaelO\UseCases\GetStudyTracker\GetStudyTracker;
use App\GaelO\UseCases\GetStudyTracker\GetStudyTrackerRequest;
use App\GaelO\UseCases\GetStudyTracker\GetStudyTrackerResponse;
use App\GaelO\UseCases\GetStudyTrackerRoleAction\GetStudyTrackerRoleAction;
use App\GaelO\UseCases\GetStudyTrackerRoleAction\GetStudyTrackerRoleActionRequest;
use App\GaelO\UseCases\GetStudyTrackerRoleAction\GetStudyTrackerRoleActionResponse;
use App\GaelO\UseCases\GetStudyTrackerByVisit\GetStudyTrackerByVisit;
use App\GaelO\UseCases\GetStudyTrackerByVisit\GetStudyTrackerByVisitRequest;
use App\GaelO\UseCases\GetStudyTrackerByVisit\GetStudyTrackerByVisitResponse;
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
        return $this->getJsonResponse($getTrackerResponse->body, $getTrackerResponse->status, $getTrackerResponse->statusText);
    }

    public function getStudyTracker(string $studyName, Request $request, GetStudyTrackerRequest $getStudyTrackerRequest, GetStudyTrackerResponse $getStudyTrackerResponse, getStudyTracker $getStudyTracker) {
        $currentUser = Auth::user();
        $getStudyTrackerRequest->currentUserId = $currentUser['id'];
        $queryParam = $request->query();
        $getStudyTrackerRequest->actionType = $queryParam['action'];
        $getStudyTrackerRequest->role = $queryParam['role'];
        $getStudyTrackerRequest->studyName = $studyName;
        $getStudyTracker->execute($getStudyTrackerRequest, $getStudyTrackerResponse);
        return $this->getJsonResponse($getStudyTrackerResponse->body, $getStudyTrackerResponse->status, $getStudyTrackerResponse->statusText);
    }

    public function getStudyTrackerRoleAction(string $studyName, string $role, Request $request, GetStudyTrackerRoleActionRequest $getStudyTrackerRoleActionRequest, GetStudyTrackerRoleActionResponse $getStudyTrackerRoleActionResponse, GetStudyTrackerRoleAction $getStudyTrackerRoleAction) {
        $currentUser = Auth::user();
        $getStudyTrackerRoleActionRequest->currentUserId = $currentUser['id'];
        $queryParam = $request->query();
        $getStudyTrackerRoleActionRequest->actionType = $queryParam['action'];
        $getStudyTrackerRoleActionRequest->role = $queryParam['role'];
        $getStudyTrackerRoleActionRequest->trackerOfRole = $role;
        $getStudyTrackerRoleActionRequest->studyName = $studyName;
        $getStudyTrackerRoleAction->execute($getStudyTrackerRoleActionRequest, $getStudyTrackerRoleActionResponse);
        return $this->getJsonResponse($getStudyTrackerRoleActionResponse->body, $getStudyTrackerRoleActionResponse->status, $getStudyTrackerRoleActionResponse->statusText);
    }

    public function getStudyTrackerByVisit(string $studyName, string $visitId, GetStudyTrackerByVisitRequest $getStudyTrackerByVisitRequest, GetStudyTrackerByVisitResponse $getStudyTrackerByVisitResponse, GetStudyTrackerByVisit $getStudyTrackerByVisit) {
        $currentUser = Auth::user();
        $getStudyTrackerByVisitRequest->currentUserId = $currentUser['id'];
        $getStudyTrackerByVisitRequest->visitId = $visitId;
        $getStudyTrackerByVisitRequest->studyName = $studyName;
        $getStudyTrackerByVisit->execute($getStudyTrackerByVisitRequest, $getStudyTrackerByVisitResponse);
        return $this->getJsonResponse($getStudyTrackerByVisitResponse->body, $getStudyTrackerByVisitResponse->status, $getStudyTrackerByVisitResponse->statusText);
    }
}
