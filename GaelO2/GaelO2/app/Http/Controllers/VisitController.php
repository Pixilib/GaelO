<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\CreateVisit\CreateVisit;
use App\GaelO\UseCases\CreateVisit\CreateVisitRequest;
use App\GaelO\UseCases\CreateVisit\CreateVisitResponse;
use App\GaelO\UseCases\DeleteVisit\DeleteVisit;
use App\GaelO\UseCases\DeleteVisit\DeleteVisitRequest;
use App\GaelO\UseCases\DeleteVisit\DeleteVisitResponse;
use App\GaelO\UseCases\GetPatientVisit\GetPatientVisit;
use App\GaelO\UseCases\GetPatientVisit\GetPatientVisitRequest;
use App\GaelO\UseCases\GetPatientVisit\GetPatientVisitResponse;
use App\GaelO\UseCases\GetVisit\GetVisit;
use App\GaelO\UseCases\GetVisit\GetVisitRequest;
use App\GaelO\UseCases\GetVisit\GetVisitResponse;
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
        $queryParam = $request->query();

        $createVisitRequest->role = $queryParam['role'];
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

    public function getVisit(int $visitId, Request $request, GetVisit $getVisit, GetVisitRequest $getVisitRequest, GetVisitResponse $getVisitResponse){

        $curentUser = Auth::user();
        $queryParam = $request->query();

        $getVisitRequest->currentUserId = $curentUser['id'];
        $getVisitRequest->visitId = $visitId;
        $getVisitRequest->role = $queryParam['role'];

        $getVisit->execute($getVisitRequest, $getVisitResponse);

        return response()->json($getVisitResponse->body)
                ->setStatusCode($getVisitResponse->status, $getVisitResponse->statusText);

    }

    public function validateDicom(int $visitId, Request $request, ValidateDicomUpload $validateDicomUpload, ValidateDicomUploadRequest $validateDicomUploadRequest, ValidateDicomUploadResponse $validateDicomUploadResponse){

        $curentUser = Auth::user();
        $validateDicomUploadRequest->currentUserId = $curentUser['id'];
        $validateDicomUploadRequest->visitId = $visitId;
        $requestData = $request->all();
        $validateDicomUploadRequest = Util::fillObject($requestData, $validateDicomUploadRequest);
        $validateDicomUpload->execute($validateDicomUploadRequest, $validateDicomUploadResponse);

    }

    public function deleteVisit(int $visitId, Request $request, DeleteVisit $deleteVisit, DeleteVisitRequest $deleteVisitRequest, DeleteVisitResponse $deleteVisitResponse){
        $curentUser = Auth::user();
        $requestData = $request->all();
        $queryParam = $request->query();
        $deleteVisitRequest = Util::fillObject($requestData, $deleteVisitRequest);
        $deleteVisitRequest->currentUserId = $curentUser['id'];
        $deleteVisitRequest->visitId = $visitId;
        $deleteVisitRequest->role = $queryParam['role'];

        $deleteVisit->execute($deleteVisitRequest, $deleteVisitResponse);

        return response()->noContent()
                ->setStatusCode($deleteVisitResponse->status, $deleteVisitResponse->statusText);
    }


}
