<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\GetTracker\GetTracker;
use App\GaelO\UseCases\GetTracker\GetTrackerRequest;
use App\GaelO\UseCases\GetTracker\GetTrackerResponse;
use App\GaelO\UseCases\GetStudyTrackerRoleAction\GetStudyTrackerRoleAction;
use App\GaelO\UseCases\GetStudyTrackerRoleAction\GetStudyTrackerRoleActionRequest;
use App\GaelO\UseCases\GetStudyTrackerRoleAction\GetStudyTrackerRoleActionResponse;
use App\GaelO\UseCases\GetStudyTrackerByVisit\GetStudyTrackerByVisit;
use App\GaelO\UseCases\GetStudyTrackerByVisit\GetStudyTrackerByVisitRequest;
use App\GaelO\UseCases\GetStudyTrackerByVisit\GetStudyTrackerByVisitResponse;
use App\GaelO\UseCases\GetStudyTrackerMessage\GetStudyTrackerMessage;
use App\GaelO\UseCases\GetStudyTrackerMessage\GetStudyTrackerMessageRequest;
use App\GaelO\UseCases\GetStudyTrackerMessage\GetStudyTrackerMessageResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrackerController extends Controller
{
    public function getTracker(Request $request, GetTrackerRequest $getTrackerRequest, GetTrackerResponse $getTrackerResponse, GetTracker $getTracker)
    {
        $currentUser = Auth::user();
        $getTrackerRequest->currentUserId = $currentUser['id'];
        $queryParam = $request->query();
        $getTrackerRequest->admin = $queryParam['admin'];
        $getTracker->execute($getTrackerRequest, $getTrackerResponse);
        return $this->getJsonResponse($getTrackerResponse->body, $getTrackerResponse->status, $getTrackerResponse->statusText);
    }

    public function getStudyTrackerByRole(Request $request,
        GetStudyTrackerRoleActionRequest $getStudyTrackerRoleActionRequest,
        GetStudyTrackerRoleActionResponse $getStudyTrackerRoleActionResponse,
        GetStudyTrackerRoleAction $getStudyTrackerRoleAction,
        GetStudyTrackerMessageRequest $getStudyTrackerMessageRequest,
        GetStudyTrackerMessageResponse $getStudyTrackerMessageResponse,
        GetStudyTrackerMessage $getStudyMessageTracker,
        string $studyName, string $role)
    {
        $currentUser = Auth::user();
        $getStudyTrackerRoleActionRequest->currentUserId = $currentUser['id'];
        $queryParam = $request->query();
        if ($role === "messages") {
            $getStudyTrackerMessageRequest->currentUserId = $currentUser['id'];
            $getStudyTrackerMessageRequest->studyName = $studyName;
            $getStudyMessageTracker->execute($getStudyTrackerMessageRequest, $getStudyTrackerMessageResponse);
            return $this->getJsonResponse($getStudyTrackerMessageResponse->body, $getStudyTrackerMessageResponse->status, $getStudyTrackerMessageResponse->statusText);
        } else {
            $getStudyTrackerRoleActionRequest->actionType = $queryParam['action'];
            $getStudyTrackerRoleActionRequest->role = $role;
            $getStudyTrackerRoleActionRequest->studyName = $studyName;
            $getStudyTrackerRoleAction->execute($getStudyTrackerRoleActionRequest, $getStudyTrackerRoleActionResponse);
            return $this->getJsonResponse($getStudyTrackerRoleActionResponse->body, $getStudyTrackerRoleActionResponse->status, $getStudyTrackerRoleActionResponse->statusText);
        }
    }

    public function getStudyTrackerByVisit(GetStudyTrackerByVisitRequest $getStudyTrackerByVisitRequest, GetStudyTrackerByVisitResponse $getStudyTrackerByVisitResponse, GetStudyTrackerByVisit $getStudyTrackerByVisit, string $studyName, string $visitId)
    {
        $currentUser = Auth::user();
        $getStudyTrackerByVisitRequest->currentUserId = $currentUser['id'];
        $getStudyTrackerByVisitRequest->visitId = $visitId;
        $getStudyTrackerByVisitRequest->studyName = $studyName;
        $getStudyTrackerByVisit->execute($getStudyTrackerByVisitRequest, $getStudyTrackerByVisitResponse);
        return $this->getJsonResponse($getStudyTrackerByVisitResponse->body, $getStudyTrackerByVisitResponse->status, $getStudyTrackerByVisitResponse->statusText);
    }
}
