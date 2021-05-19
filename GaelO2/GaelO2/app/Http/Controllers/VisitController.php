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
use App\GaelO\UseCases\GetVisitsFromStudy\GetVisitsFromStudy;
use App\GaelO\UseCases\GetVisitsFromStudy\GetVisitsFromStudyRequest;
use App\GaelO\UseCases\GetVisitsFromStudy\GetVisitsFromStudyResponse;
use App\GaelO\UseCases\ModifyCorrectiveAction\ModifyCorrectiveAction;
use App\GaelO\UseCases\ModifyCorrectiveAction\ModifyCorrectiveActionRequest;
use App\GaelO\UseCases\ModifyCorrectiveAction\ModifyCorrectiveActionResponse;
use App\GaelO\UseCases\ModifyQualityControl\ModifyQualityControl;
use App\GaelO\UseCases\ModifyQualityControl\ModifyQualityControlRequest;
use App\GaelO\UseCases\ModifyQualityControl\ModifyQualityControlResponse;
use App\GaelO\UseCases\ModifyQualityControlReset\ModifyQualityControlReset;
use App\GaelO\UseCases\ModifyQualityControlReset\ModifyQualityControlResetRequest;
use App\GaelO\UseCases\ModifyQualityControlReset\ModifyQualityControlResetResponse;
use App\GaelO\UseCases\ReactivateVisit\ReactivateVisit;
use App\GaelO\UseCases\ReactivateVisit\ReactivateVisitRequest;
use App\GaelO\UseCases\ReactivateVisit\ReactivateVisitResponse;
use App\GaelO\UseCases\ValidateDicomUpload\ValidateDicomUpload;
use App\GaelO\UseCases\ValidateDicomUpload\ValidateDicomUploadRequest;
use App\GaelO\UseCases\ValidateDicomUpload\ValidateDicomUploadResponse;
use App\GaelO\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VisitController extends Controller
{
    public function createVisit(String $studyName, String $visitGroupId, String $visitTypeId, Request $request, CreateVisit $createVisit, CreateVisitRequest $createVisitRequest, CreateVisitResponse $createVisitResponse)
    {
        $curentUser = Auth::user();

        $createVisitRequest->currentUserId = $curentUser['id'];
        $createVisitRequest->studyName = $studyName;
        $createVisitRequest->visitGroupId = $visitGroupId;
        $createVisitRequest->visitTypeId = $visitTypeId;

        $requestData = $request->all();
        $createVisitRequest = Util::fillObject($requestData, $createVisitRequest);
        $createVisit->execute($createVisitRequest, $createVisitResponse);
        return $this->getJsonResponse($createVisitResponse->body, $createVisitResponse->status, $createVisitResponse->statusText);
    }

    public function getVisit(string $studyName, int $visitId, Request $request, GetVisit $getVisit, GetVisitRequest $getVisitRequest, GetVisitResponse $getVisitResponse)
    {

        $curentUser = Auth::user();
        $queryParam = $request->query();

        $getVisitRequest->currentUserId = $curentUser['id'];
        $getVisitRequest->visitId = $visitId;
        $getVisitRequest->studyName = $studyName;
        $getVisitRequest->role = $queryParam['role'];

        $getVisit->execute($getVisitRequest, $getVisitResponse);

        return $this->getJsonResponse($getVisitResponse->body, $getVisitResponse->status, $getVisitResponse->statusText);
    }

    public function getVisitsFromStudy(string $studyName, Request $request, GetVisitsFromStudy $getVisitsFromStudy, GetVisitsFromStudyRequest $getVisitsFromStudyRequest, GetVisitsFromStudyResponse $getVisitsFromStudyResponse)
    {
        $curentUser = Auth::user();

        $getVisitsFromStudyRequest->currentUserId = $curentUser['id'];
        $getVisitsFromStudyRequest->studyName = $studyName;

        $getVisitsFromStudy->execute($getVisitsFromStudyRequest, $getVisitsFromStudyResponse);

        return $this->getJsonResponse($getVisitsFromStudyResponse->body, $getVisitsFromStudyResponse->status, $getVisitsFromStudyResponse->statusText);
    }

    public function validateDicom(int $visitId, Request $request, ValidateDicomUpload $validateDicomUpload, ValidateDicomUploadRequest $validateDicomUploadRequest, ValidateDicomUploadResponse $validateDicomUploadResponse)
    {

        $curentUser = Auth::user();
        $validateDicomUploadRequest->currentUserId = $curentUser['id'];
        $validateDicomUploadRequest->visitId = $visitId;
        $requestData = $request->all();
        $validateDicomUploadRequest = Util::fillObject($requestData, $validateDicomUploadRequest);
        $validateDicomUpload->execute($validateDicomUploadRequest, $validateDicomUploadResponse);

        return $this->getJsonResponse($validateDicomUploadResponse->body, $validateDicomUploadResponse->status, $validateDicomUploadResponse->statusText);
    }

    public function deleteVisit(int $visitId, Request $request, DeleteVisit $deleteVisit, DeleteVisitRequest $deleteVisitRequest, DeleteVisitResponse $deleteVisitResponse)
    {
        $curentUser = Auth::user();
        $requestData = $request->all();
        $queryParam = $request->query();
        $deleteVisitRequest = Util::fillObject($requestData, $deleteVisitRequest);
        $deleteVisitRequest->currentUserId = $curentUser['id'];
        $deleteVisitRequest->visitId = $visitId;
        $deleteVisitRequest->role = $queryParam['role'];

        $deleteVisit->execute($deleteVisitRequest, $deleteVisitResponse);

        return $this->getJsonResponse($deleteVisitResponse->body, $deleteVisitResponse->status, $deleteVisitResponse->statusText);
    }

    public function modifyQualityControl(int $visitId, Request $request, ModifyQualityControl $modifyQualityControl, ModifyQualityControlRequest $modifyQualityControlRequest, ModifyQualityControlResponse $modifyQualityControlResponse)
    {
        $curentUser = Auth::user();
        $requestData = $request->all();

        $modifyQualityControlRequest = Util::fillObject($requestData, $modifyQualityControlRequest);
        $modifyQualityControlRequest->currentUserId = $curentUser['id'];
        $modifyQualityControlRequest->visitId = $visitId;

        $modifyQualityControl->execute($modifyQualityControlRequest, $modifyQualityControlResponse);

        return $this->getJsonResponse($modifyQualityControlResponse->body, $modifyQualityControlResponse->status, $modifyQualityControlResponse->statusText);
    }

    public function modifyQualityControlReset(int $visitId, ModifyQualityControlReset $modifyQualityControlReset, ModifyQualityControlResetRequest $modifyQualityControlResetRequest, ModifyQualityControlResetResponse $modifyQualityControlResetResponse)
    {
        $curentUser = Auth::user();

        $modifyQualityControlResetRequest->currentUserId = $curentUser['id'];
        $modifyQualityControlResetRequest->visitId = $visitId;

        $modifyQualityControlReset->execute($modifyQualityControlResetRequest, $modifyQualityControlResetResponse);

        return $this->getJsonResponse($modifyQualityControlResetResponse->body, $modifyQualityControlResetResponse->status, $modifyQualityControlResetResponse->statusText);
    }

    public function modifyCorrectiveAction(int $visitId, Request $request, ModifyCorrectiveAction $modifyCorrectiveAction, ModifyCorrectiveActionRequest $modifyCorrectiveActionRequest, ModifyCorrectiveActionResponse $modifyCorrectiveActionResponse)
    {
        $curentUser = Auth::user();
        $requestData = $request->all();

        $modifyCorrectiveActionRequest = Util::fillObject($requestData, $modifyCorrectiveActionRequest);
        $modifyCorrectiveActionRequest->currentUserId = $curentUser['id'];
        $modifyCorrectiveActionRequest->visitId = $visitId;

        $modifyCorrectiveAction->execute($modifyCorrectiveActionRequest, $modifyCorrectiveActionResponse);

        return $this->getJsonResponse($modifyCorrectiveActionResponse->body, $modifyCorrectiveActionResponse->status, $modifyCorrectiveActionResponse->statusText);
    }

    public function reactivateVisit(int $visitId, ReactivateVisit $reactivateVisit, ReactivateVisitRequest $reactivateVisitRequest, ReactivateVisitResponse $reactivateVisitResponse)
    {
        $currentUser = Auth::user();

        $reactivateVisitRequest->currentUserId = $currentUser['id'];
        $reactivateVisitRequest->visitId = $visitId;

        $reactivateVisit->execute($reactivateVisitRequest, $reactivateVisitResponse);

        return $this->getJsonResponse($reactivateVisitResponse->body, $reactivateVisitResponse->status, $reactivateVisitResponse->statusText);
    }
}
