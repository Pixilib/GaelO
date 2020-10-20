<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\CreatePatient\CreatePatient;
use App\GaelO\UseCases\CreatePatient\CreatePatientRequest;
use App\GaelO\UseCases\CreatePatient\CreatePatientResponse;
use App\GaelO\UseCases\GetPatient\GetPatient;
use App\GaelO\UseCases\GetPatient\GetPatientRequest;
use App\GaelO\UseCases\GetPatient\GetPatientResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PatientController extends Controller
{
    public function createPatient(string $studyName, Request $request, CreatePatient $createPatient, CreatePatientRequest $createPatientRequest, CreatePatientResponse $createPatientResponse){

        $currentUser = Auth::user();
        $createPatientRequest->patients = $request->all() ;
        $createPatientRequest->studyName = $studyName;
        $createPatientRequest->currentUserCode = $currentUser['id'];
        $createPatient->execute($createPatientRequest, $createPatientResponse);

        return response()->json($createPatientResponse->body)->setStatusCode($createPatientResponse->status, $createPatientResponse->statusText);

    }

    public function getPatient(int $code=0, GetPatientRequest $getPatientRequest, GetPatientResponse $getPatientResponse, GetPatient $getPatient) {
        $getPatientRequest->code = $code;
        $getPatient->execute($getPatientRequest, $getPatientResponse);
        return response()->json($getPatientResponse->body)
                ->setStatusCode($getPatientResponse->status, $getPatientResponse->statusText);
    }

}
