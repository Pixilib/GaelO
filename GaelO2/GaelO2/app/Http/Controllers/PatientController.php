<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\CreatePatient\CreatePatient;
use App\GaelO\UseCases\CreatePatient\CreatePatientRequest;
use App\GaelO\UseCases\CreatePatient\CreatePatientResponse;
use App\GaelO\UseCases\GetPatient\GetPatient;
use App\GaelO\UseCases\GetPatient\GetPatientRequest;
use App\GaelO\UseCases\GetPatient\GetPatientResponse;
use App\GaelO\UseCases\GetPatientFromStudy\GetPatientFromStudy;
use App\GaelO\UseCases\GetPatientFromStudy\GetPatientFromStudyRequest;
use App\GaelO\UseCases\GetPatientFromStudy\GetPatientFromStudyResponse;
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

    public function getPatient(int $code=0, Request $request, GetPatientRequest $getPatientRequest, GetPatientResponse $getPatientResponse, GetPatient $getPatient) {
        $currentUser = Auth::user();
        $queryParam = $request->query();
        $getPatientRequest->role = $queryParam['role'];
        $getPatientRequest->currentUserId = $currentUser['id'];
        $getPatientRequest->code = $code;
        $getPatient->execute($getPatientRequest, $getPatientResponse);
        return response()->json($getPatientResponse->body)
                ->setStatusCode($getPatientResponse->status, $getPatientResponse->statusText);
    }

    public function getPatientFromStudy(String $studyName, Request $request, GetPatientFromStudyRequest $getPatientRequest, GetPatientFromStudyResponse $getPatientResponse, GetPatientFromStudy $getPatient) {
        $currentUser = Auth::user();
        $queryParam = $request->query();
        $getPatientRequest->role = $queryParam['role'];
        $getPatientRequest->currentUserId = $currentUser['id'];
        $getPatientRequest->studyName = $studyName;
        $getPatient->execute($getPatientRequest, $getPatientResponse);
        return response()->json($getPatientResponse->body)
                ->setStatusCode($getPatientResponse->status, $getPatientResponse->statusText);
    }

    public function getPatientVisit(int $patientCode, Request $request, GetPatientVisit $getPatientVisit, GetPatientVisitRequest $getPatientVisitRequest, GetPatientVisitResponse $getPatientVisitResponse){
        $currentUser = Auth::user();
        $queryParam = $request->query();
        $getPatientVisitRequest->role = $queryParam['role'];
        $getPatientVisitRequest->currentUserId = $currentUser['id'];
        $getPatientVisitRequest->patientCode = $patientCode;

        $getPatientVisit->execute($getPatientVisitRequest, $getPatientVisitResponse);

        return response()->json($getPatientVisitResponse->body)
                ->setStatusCode($getPatientVisitResponse->status, $getPatientVisitResponse->statusText);

    }

    public function modifyPatient(int $patientCode, Request $request, ModifyPatient $modifyPatient, ModifyPatientRequest $modifyPatientRequest, ModifyPatientResponse $modifyPatientResponse){
        $currentUser = Auth::user();
        $requestData = $request->all();
        //Fill DTO with all other request data
        $modifyPatientRequest = Util::fillObject($requestData, $modifyPatientRequest);
        $modifyPatientRequest->patientCode = $patientCode;
        $modifyPatientRequest->currentUserId = $currentUser['id'];
        $modifyPatient->execute($modifyPatientRequest, $modifyPatientResponse);

        return response()->json($modifyPatientResponse->body)
                ->setStatusCode($modifyPatientResponse->status, $modifyPatientResponse->statusText);

    }

    public function modifyPatientInclusionStatus(int $patientCode, Request $request, ModifyPatientWithdraw $modifyPatientWithdraw, ModifyPatientWithdrawRequest $modifyPatientWithdrawRequest, ModifyPatientWithdrawResponse $modifyPatientWithdrawResponse){
        $currentUser = Auth::user();
        $requestData = $request->all();
        //Fill DTO with all other request data
        $modifyPatientWithdrawRequest = Util::fillObject($requestData, $modifyPatientWithdrawRequest);
        $modifyPatientWithdrawRequest->currentUserId = $currentUser['id'];
        $modifyPatientWithdrawRequest->patientCode = $patientCode;
        $modifyPatientWithdraw->execute($modifyPatientWithdrawRequest, $modifyPatientWithdrawResponse);

        return response()->json($modifyPatientWithdrawResponse->body)
                ->setStatusCode($modifyPatientWithdrawResponse->status, $modifyPatientWithdrawResponse->statusText);
    }

}
