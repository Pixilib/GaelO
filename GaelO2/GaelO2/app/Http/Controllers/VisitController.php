<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\CreateVisit\CreateVisit;
use App\GaelO\UseCases\CreateVisit\CreateVisitRequest;
use App\GaelO\UseCases\CreateVisit\CreateVisitResponse;
use App\GaelO\UseCases\DeleteVisit\DeleteVisit;
use App\GaelO\UseCases\DeleteVisit\DeleteVisitRequest;
use App\GaelO\UseCases\DeleteVisit\DeleteVisitResponse;
use App\GaelO\UseCases\GetVisit\GetVisit;
use App\GaelO\UseCases\GetVisit\GetVisitRequest;
use App\GaelO\UseCases\GetVisit\GetVisitResponse;
use App\GaelO\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VisitController extends Controller
{
    public function createVisit(String $studyName, String $visitGroupId, String $visitTypeId, Request $request, CreateVisit $createVisit, CreateVisitRequest $createVisitRequest, CreateVisitResponse $createVisitResponse) {
        $curentUser = Auth::user();
        $createVisitRequest->currentUserId = $curentUser['id'];
        $createVisitRequest->studyName = $studyName;
        $createVisitRequest->visitGroupId = $visitGroupId;
        $createVisitRequest->visitTypeId = $visitTypeId;
        $requestData = $request->all();
        $createVisitRequest = Util::fillObject($requestData, $createVisitRequest);
        $createVisit->execute($createVisitRequest, $createVisitResponse);

        return response()->noContent()
                ->setStatusCode($createVisitResponse->status, $createVisitResponse->statusText);
    }

    public function getVisit(String $studyName, int $visitGroupId, int $visitTypeId, int $visitId, GetVisit $getVisit, GetVisitRequest $getVisitRequest, GetVisitResponse $getVisitResponse){
        $getVisitRequest->visitId = $visitId;

        $getVisit->execute($getVisitRequest, $getVisitResponse);

        return response()->json($getVisitResponse->body)
                ->setStatusCode($getVisitResponse->status, $getVisitResponse->statusText);

    }
}
