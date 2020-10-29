<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\CreateVisit\CreateVisit;
use App\GaelO\UseCases\CreateVisit\CreateVisitRequest;
use App\GaelO\UseCases\CreateVisit\CreateVisitResponse;
use App\GaelO\UseCases\GetPatientVisit\GetPatientVisit;
use App\GaelO\UseCases\GetPatientVisit\GetPatientVisitRequest;
use App\GaelO\UseCases\GetPatientVisit\GetPatientVisitResponse;
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
        $queryParam = $request->query();
        $createVisitRequest->role = $queryParam['role'];
        $requestData = $request->all();
        $createVisitRequest = Util::fillObject($requestData, $createVisitRequest);
        $createVisit->execute($createVisitRequest, $createVisitResponse);

        return response()->noContent()
                ->setStatusCode($createVisitResponse->status, $createVisitResponse->statusText);
    }

    public function getVisit(int $visitId = 0, GetVisit $getVisit, Request $request, GetVisitRequest $getVisitRequest, GetVisitResponse $getVisitResponse){
        $getVisitRequest->visitId = $visitId;
        $queryParam = $request->query();
        $getVisitRequest->role = $queryParam['role'];

        $getVisit->execute($getVisitRequest, $getVisitResponse);

        return response()->json($getVisitResponse->body)
                ->setStatusCode($getVisitResponse->status, $getVisitResponse->statusText);

    }

    public function getPatientVisit(int $visitId = 0, int $patientCode, Request $request, GetPatientVisit $getPatientVisit, GetPatientVisitRequest $getPatientVisitRequest, GetPatientVisitResponse $getPatientVisitResponse){
        $getPatientVisitRequest->visitId = $visitId;
        $getPatientVisitRequest->patientCode = $patientCode;
        $queryParam = $request->query();
        $getPatientVisitRequest->role = $queryParam['role'];

        $getPatientVisit->execute($getPatientVisitRequest, $getPatientVisitResponse);

        return response()->json($getPatientVisitResponse->body)
                ->setStatusCode($getPatientVisitResponse->status, $getPatientVisitResponse->statusText);

    }
}
