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
use App\GaelO\UseCases\ReverseProxyTus\ReverseProxyTus;
use App\GaelO\UseCases\ReverseProxyTus\ReverseProxyTusRequest;
use App\GaelO\UseCases\ReverseProxyTus\ReverseProxyTusResponse;
use App\GaelO\UseCases\ValidateDicomUpload\ValidateDicomUpload;
use App\GaelO\UseCases\ValidateDicomUpload\ValidateDicomUploadRequest;
use App\GaelO\UseCases\ValidateDicomUpload\ValidateDicomUploadResponse;
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

    public function validateDicom(int $visitId, Request $request, ValidateDicomUpload $validateDicomUpload, ValidateDicomUploadRequest $validateDicomUploadRequest, ValidateDicomUploadResponse $validateDicomUploadResponse){

        $curentUser = Auth::user();
        $validateDicomUploadRequest->currentUserId = $curentUser['id'];
        $validateDicomUploadRequest->visitId = $visitId;
        $requestData = $request->all();
        $validateDicomUploadRequest = Util::fillObject($requestData, $validateDicomUploadRequest);
        $validateDicomUpload->execute($validateDicomUploadRequest, $validateDicomUploadResponse);

    }

    public function tusUpload(Request $request, ReverseProxyTus $reverseProxyTus, ReverseProxyTusRequest $reverseProxyTusRequest, ReverseProxyTusResponse $reverseProxyTusResponse){
        $curentUser = Auth::user();
        $reverseProxyTusRequest->currentUserId = $curentUser['id'];
        error_log(print_r($request, true));
    }
}
