<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\CreatePatient\CreatePatient;
use App\GaelO\UseCases\CreatePatient\CreatePatientRequest;
use App\GaelO\UseCases\CreatePatient\CreatePatientResponse;
use App\GaelO\UseCases\DeletePatient\DeletePatient;
use App\GaelO\UseCases\DeletePatient\DeletePatientRequest;
use App\GaelO\UseCases\DeletePatient\DeletePatientResponse;
use App\GaelO\UseCases\GetPatient\GetPatient;
use App\GaelO\UseCases\GetPatient\GetPatientRequest;
use App\GaelO\UseCases\GetPatient\GetPatientResponse;
use App\GaelO\UseCases\GetPatientDetails\GetPatientDetails;
use App\GaelO\UseCases\GetPatientDetails\GetPatientDetailsRequest;
use App\GaelO\UseCases\GetPatientDetails\GetPatientDetailsResponse;
use App\GaelO\UseCases\ReactivatePatient\ReactivatePatient;
use App\GaelO\UseCases\ReactivatePatient\ReactivatePatientRequest;
use App\GaelO\UseCases\ReactivatePatient\ReactivatePatientResponse;
use App\GaelO\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PatientController extends Controller
{
    public function createPatient(Request $request, CreatePatient $createPatient, CreatePatientRequest $createPatientRequest, CreatePatientResponse $createPatientResponse){

        $currentUser = Auth::user();
        $requestData = $request->all();
        $createPatientRequest = Util::fillObject($requestData, $createPatientRequest);
        $createPatientRequest->currentUserCode = $currentUser['code'];
        $createPatient->execute($createPatientRequest, $createPatientResponse);

        return response()->noContent()
                ->setStatusCode($createPatientResponse->status, $createPatientResponse->statusText);

    }

    public function getPatient(Request $request, GetPatient $getPatient, GetPatientRequest $getPatientRequest, GetPatientResponse $getPatientResponse, GetPatientDetails $getPatientDetails, GetPatientDetailsRequest $getPatientDetailsRequest, GetPatientDetailsResponse $getPatientDetailsResponse){
        //RECUPERATION DES QUERY PARAM (? dans URL)
        $queryParam = $request->query();
        if(array_key_exists('expand', $queryParam) ){
            $getPatientDetails->execute($getPatientDetailsRequest, $getPatientDetailsResponse);
            return response()->json($getPatientDetailsResponse->body)
            ->setStatusCode($getPatientDetailsResponse->status, $getPatientDetailsResponse->statusText);
        }else {
            $getPatient->execute($getPatientRequest, $getPatientResponse);
            return response()->json($getPatientResponse->body)
            ->setStatusCode($getPatientResponse->status, $getPatientResponse->statusText);
        }

    }

    public function deletePatient(String $patientName, DeletePatient $deletePatient,  DeletePatientRequest $deletePatientRequest, DeletePatientResponse $deletePatientResponse){
        $currentUser = Auth::user();
        $deletePatientRequest->currentUserCode = $currentUser['code'];
        $deletePatientRequest->patientName = $patientName;
        $deletePatient->execute($deletePatientRequest, $deletePatientResponse);

        return response()->noContent()
                ->setStatusCode($deletePatientResponse->status, $deletePatientResponse->statusText);

    }

    public function reactivatePatient(string $patientName, ReactivatePatient $reactivatePatient, ReactivatePatientRequest $reactivatePatientRequest, ReactivatePatientResponse $reactivatePatientResponse){
        $currentUser = Auth::user();
        $reactivatePatientRequest->currentUserCode = $currentUser['code'];
        $reactivatePatientRequest->patientName = $patientName;
        $reactivatePatient->execute($reactivatePatientRequest, $reactivatePatientResponse);
        return response()->noContent()
                ->setStatusCode($reactivatePatientResponse->status, $reactivatePatientResponse->statusText);
    }

}
