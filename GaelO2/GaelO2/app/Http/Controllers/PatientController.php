<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\GetCreatableVisits\GetCreatableVisits;
use App\GaelO\UseCases\GetCreatableVisits\GetCreatableVisitsRequest;
use App\GaelO\UseCases\GetCreatableVisits\GetCreatableVisitsResponse;
use App\GaelO\UseCases\GetPatient\GetPatient;
use App\GaelO\UseCases\GetPatient\GetPatientRequest;
use App\GaelO\UseCases\GetPatient\GetPatientResponse;
use App\GaelO\UseCases\GetPatientVisit\GetPatientVisit;
use App\GaelO\UseCases\GetPatientVisit\GetPatientVisitRequest;
use App\GaelO\UseCases\GetPatientVisit\GetPatientVisitResponse;
use App\GaelO\UseCases\ModifyPatient\ModifyPatient;
use App\GaelO\UseCases\ModifyPatient\ModifyPatientRequest;
use App\GaelO\UseCases\ModifyPatient\ModifyPatientResponse;
use App\GaelO\UseCases\ModifyPatientWithdraw\ModifyPatientWithdraw;
use App\GaelO\UseCases\ModifyPatientWithdraw\ModifyPatientWithdrawRequest;
use App\GaelO\UseCases\ModifyPatientWithdraw\ModifyPatientWithdrawResponse;

use App\GaelO\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PatientController extends Controller
{

    public function getPatient(int $code = null, Request $request, GetPatientRequest $getPatientRequest, GetPatientResponse $getPatientResponse, GetPatient $getPatient)
    {
        $currentUser = Auth::user();
        $queryParam = $request->query();
        $getPatientRequest->role = $queryParam['role'];
        $getPatientRequest->currentUserId = $currentUser['id'];
        $getPatientRequest->code = $code;
        $getPatient->execute($getPatientRequest, $getPatientResponse);
        return $this->getJsonResponse($getPatientResponse->body, $getPatientResponse->status, $getPatientResponse->statusText);
    }

    public function getPatientVisit(string $studyName, int $patientCode, Request $request, GetPatientVisit $getPatientVisit, GetPatientVisitRequest $getPatientVisitRequest, GetPatientVisitResponse $getPatientVisitResponse)
    {
        $currentUser = Auth::user();
        $queryParam = $request->query();
        $getPatientVisitRequest->role = $queryParam['role'];
        $getPatientVisitRequest->withTrashed =  array_key_exists('withTrashed', $queryParam);
        $getPatientVisitRequest->currentUserId = $currentUser['id'];
        $getPatientVisitRequest->patientCode = $patientCode;
        $getPatientVisitRequest->studyName = $studyName;

        $getPatientVisit->execute($getPatientVisitRequest, $getPatientVisitResponse);

        return $this->getJsonResponse($getPatientVisitResponse->body, $getPatientVisitResponse->status, $getPatientVisitResponse->statusText);
    }

    public function modifyPatient(int $patientCode, Request $request, ModifyPatient $modifyPatient, ModifyPatientRequest $modifyPatientRequest, ModifyPatientResponse $modifyPatientResponse)
    {
        $currentUser = Auth::user();
        $requestData = $request->all();

        $modifyPatientRequest = Util::fillObject($requestData, $modifyPatientRequest);
        $modifyPatientRequest->patientCode = $patientCode;
        $modifyPatientRequest->currentUserId = $currentUser['id'];
        $modifyPatient->execute($modifyPatientRequest, $modifyPatientResponse);

        return $this->getJsonResponse($modifyPatientResponse->body, $modifyPatientResponse->status, $modifyPatientResponse->statusText);
    }

    public function modifyPatientInclusionStatus(int $patientCode, Request $request, ModifyPatientWithdraw $modifyPatientWithdraw, ModifyPatientWithdrawRequest $modifyPatientWithdrawRequest, ModifyPatientWithdrawResponse $modifyPatientWithdrawResponse)
    {
        $currentUser = Auth::user();
        $requestData = $request->all();

        $modifyPatientWithdrawRequest = Util::fillObject($requestData, $modifyPatientWithdrawRequest);
        $modifyPatientWithdrawRequest->currentUserId = $currentUser['id'];
        $modifyPatientWithdrawRequest->patientCode = $patientCode;
        $modifyPatientWithdraw->execute($modifyPatientWithdrawRequest, $modifyPatientWithdrawResponse);

        return $this->getJsonResponse($modifyPatientWithdrawResponse->body, $modifyPatientWithdrawResponse->status, $modifyPatientWithdrawResponse->statusText);
    }

    public function getCreatableVisits(string $studyName, int $patientCode, GetCreatableVisits $getCreatableVisits, GetCreatableVisitsRequest $getCreatableVisitsRequest, GetCreatableVisitsResponse $getCreatableVisitsResponse)
    {

        $currentUser = Auth::user();

        $getCreatableVisitsRequest->currentUserId = $currentUser['id'];
        $getCreatableVisitsRequest->patientCode = $patientCode;
        $getCreatableVisitsRequest->studyName = $studyName;

        $getCreatableVisits->execute($getCreatableVisitsRequest, $getCreatableVisitsResponse);

        return $this->getJsonResponse($getCreatableVisitsResponse->body, $getCreatableVisitsResponse->status, $getCreatableVisitsResponse->statusText);
    }
}
