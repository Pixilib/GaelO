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
use App\GaelO\UseCases\ModifyVisitDate\ModifyVisitDate;
use App\GaelO\UseCases\ModifyVisitDate\ModifyVisitDateRequest;
use App\GaelO\UseCases\ModifyVisitDate\ModifyVisitDateResponse;
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
    public function createVisit(Request $request, CreateVisit $createVisit, CreateVisitRequest $createVisitRequest, CreateVisitResponse $createVisitResponse, String $visitTypeId)
    {
        $currentUser = Auth::user();

        $createVisitRequest->currentUserId = $currentUser['id'];
        $createVisitRequest->visitTypeId = $visitTypeId;
        $queryParam = $request->query();
        $createVisitRequest->role = $queryParam['role'];

        $requestData = $request->all();
        $createVisitRequest = Util::fillObject($requestData, $createVisitRequest);
        $createVisit->execute($createVisitRequest, $createVisitResponse);
        return $this->getJsonResponse($createVisitResponse->body, $createVisitResponse->status, $createVisitResponse->statusText);
    }

    public function getVisit(Request $request, GetVisit $getVisit, GetVisitRequest $getVisitRequest, GetVisitResponse $getVisitResponse, int $visitId)
    {

        $currentUser = Auth::user();
        $queryParam = $request->query();

        $getVisitRequest->currentUserId = $currentUser['id'];
        $getVisitRequest->visitId = $visitId;
        $getVisitRequest->studyName = $queryParam['studyName'];
        $getVisitRequest->role = $queryParam['role'];

        $getVisit->execute($getVisitRequest, $getVisitResponse);

        return $this->getJsonResponse($getVisitResponse->body, $getVisitResponse->status, $getVisitResponse->statusText);
    }

    public function getVisitsFromStudy(Request $request, GetVisitsFromStudy $getVisitsFromStudy, GetVisitsFromStudyRequest $getVisitsFromStudyRequest, GetVisitsFromStudyResponse $getVisitsFromStudyResponse, string $studyName)
    {
        $currentUser = Auth::user();

        $queryParam = $request->query();
        $getVisitsFromStudyRequest->currentUserId = $currentUser['id'];
        $getVisitsFromStudyRequest->studyName = $studyName;
        if(isset($queryParam['visitType'])) $getVisitsFromStudyRequest->visitTypeId = $queryParam['visitType'];
        $getVisitsFromStudy->execute($getVisitsFromStudyRequest, $getVisitsFromStudyResponse);

        return $this->getJsonResponse($getVisitsFromStudyResponse->body, $getVisitsFromStudyResponse->status, $getVisitsFromStudyResponse->statusText);
    }

    public function validateDicom(Request $request, ValidateDicomUpload $validateDicomUpload, ValidateDicomUploadRequest $validateDicomUploadRequest, ValidateDicomUploadResponse $validateDicomUploadResponse, int $visitId)
    {

        $currentUser = Auth::user();
        $validateDicomUploadRequest->currentUserId = $currentUser['id'];
        $validateDicomUploadRequest->visitId = $visitId;
        $requestData = $request->all();
        $validateDicomUploadRequest = Util::fillObject($requestData, $validateDicomUploadRequest);
        $validateDicomUpload->execute($validateDicomUploadRequest, $validateDicomUploadResponse);

        return $this->getJsonResponse($validateDicomUploadResponse->body, $validateDicomUploadResponse->status, $validateDicomUploadResponse->statusText);
    }

    public function deleteVisit(Request $request, DeleteVisit $deleteVisit, DeleteVisitRequest $deleteVisitRequest, DeleteVisitResponse $deleteVisitResponse, int $visitId)
    {
        $currentUser = Auth::user();
        $requestData = $request->all();
        $queryParam = $request->query();
        $deleteVisitRequest = Util::fillObject($requestData, $deleteVisitRequest);
        $deleteVisitRequest->currentUserId = $currentUser['id'];
        $deleteVisitRequest->visitId = $visitId;
        $deleteVisitRequest->role = $queryParam['role'];

        $deleteVisit->execute($deleteVisitRequest, $deleteVisitResponse);

        return $this->getJsonResponse($deleteVisitResponse->body, $deleteVisitResponse->status, $deleteVisitResponse->statusText);
    }

    public function modifyQualityControl(Request $request, ModifyQualityControl $modifyQualityControl, ModifyQualityControlRequest $modifyQualityControlRequest, ModifyQualityControlResponse $modifyQualityControlResponse, int $visitId)
    {
        $currentUser = Auth::user();
        $requestData = $request->all();

        $modifyQualityControlRequest = Util::fillObject($requestData, $modifyQualityControlRequest);
        $modifyQualityControlRequest->currentUserId = $currentUser['id'];
        $modifyQualityControlRequest->visitId = $visitId;

        $modifyQualityControl->execute($modifyQualityControlRequest, $modifyQualityControlResponse);

        return $this->getJsonResponse($modifyQualityControlResponse->body, $modifyQualityControlResponse->status, $modifyQualityControlResponse->statusText);
    }

    public function modifyQualityControlReset(Request $request, ModifyQualityControlReset $modifyQualityControlReset, ModifyQualityControlResetRequest $modifyQualityControlResetRequest, ModifyQualityControlResetResponse $modifyQualityControlResetResponse, int $visitId)
    {
        $currentUser = Auth::user();
        $requestData = $request->all();

        $modifyQualityControlResetRequest->currentUserId = $currentUser['id'];
        $modifyQualityControlResetRequest->visitId = $visitId;
        $modifyQualityControlResetRequest = Util::fillObject($requestData, $modifyQualityControlResetRequest);

        $modifyQualityControlReset->execute($modifyQualityControlResetRequest, $modifyQualityControlResetResponse);

        return $this->getJsonResponse($modifyQualityControlResetResponse->body, $modifyQualityControlResetResponse->status, $modifyQualityControlResetResponse->statusText);
    }

    public function modifyCorrectiveAction(Request $request, ModifyCorrectiveAction $modifyCorrectiveAction, ModifyCorrectiveActionRequest $modifyCorrectiveActionRequest, ModifyCorrectiveActionResponse $modifyCorrectiveActionResponse, int $visitId)
    {
        $currentUser = Auth::user();
        $requestData = $request->all();

        $modifyCorrectiveActionRequest = Util::fillObject($requestData, $modifyCorrectiveActionRequest);
        $modifyCorrectiveActionRequest->currentUserId = $currentUser['id'];
        $modifyCorrectiveActionRequest->visitId = $visitId;

        $modifyCorrectiveAction->execute($modifyCorrectiveActionRequest, $modifyCorrectiveActionResponse);

        return $this->getJsonResponse($modifyCorrectiveActionResponse->body, $modifyCorrectiveActionResponse->status, $modifyCorrectiveActionResponse->statusText);
    }

    public function modifyVisitDate(Request $request, ModifyVisitDate $modifyVisitDate, ModifyVisitDateRequest $modifyVisitDateRequest, ModifyVisitDateResponse $modifyVisitDateResponse, int $visitId){
        $currentUser = Auth::user();
        $requestData = $request->all();

        $modifyCorrectiveActionRequest = Util::fillObject($requestData, $modifyVisitDateRequest);
        $modifyCorrectiveActionRequest->currentUserId = $currentUser['id'];
        $modifyCorrectiveActionRequest->visitId = $visitId;

        $modifyVisitDate->execute($modifyVisitDateRequest, $modifyVisitDateResponse);

        return $this->getJsonResponse($modifyVisitDateResponse->body, $modifyVisitDateResponse->status, $modifyVisitDateResponse->statusText);
    }

    public function reactivateVisit(ReactivateVisit $reactivateVisit, ReactivateVisitRequest $reactivateVisitRequest, ReactivateVisitResponse $reactivateVisitResponse, int $visitId)
    {
        $currentUser = Auth::user();

        $reactivateVisitRequest->currentUserId = $currentUser['id'];
        $reactivateVisitRequest->visitId = $visitId;

        $reactivateVisit->execute($reactivateVisitRequest, $reactivateVisitResponse);

        return $this->getJsonResponse($reactivateVisitResponse->body, $reactivateVisitResponse->status, $reactivateVisitResponse->statusText);
    }
}
