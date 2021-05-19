<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\RequestUnlock\RequestUnlock;
use App\GaelO\UseCases\RequestUnlock\RequestUnlockRequest;
use App\GaelO\UseCases\RequestUnlock\RequestUnlockResponse;
use App\GaelO\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AskUnlockController extends Controller
{
    public function askUnlock(string $studyName, int $visitId, Request $request, RequestUnlock $requestUnlock, RequestUnlockRequest $requestUnlockRequest, RequestUnlockResponse $requestUnlockResponse)
    {
        $currentUser = Auth::user();
        $queryParam = $request->query();

        $requestData = $request->all();
        $requestUnlockRequest = Util::fillObject($requestData, $requestUnlockRequest);

        $requestUnlockRequest->studyName = $studyName;
        $requestUnlockRequest->currentUserId = $currentUser['id'];
        $requestUnlockRequest->visitId = $visitId;
        $requestUnlockRequest->role = $queryParam['role'];

        $requestUnlock->execute($requestUnlockRequest, $requestUnlockResponse);
        return $this->getJsonResponse($requestUnlockResponse->body, $requestUnlockResponse->status, $requestUnlockResponse->statusText);
    }
}
