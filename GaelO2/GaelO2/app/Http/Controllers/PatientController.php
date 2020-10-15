<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\CreatePatient\CreatePatient;
use App\GaelO\UseCases\CreatePatient\CreatePatientRequest;
use App\GaelO\UseCases\CreatePatient\CreatePatientResponse;
use App\GaelO\UseCases\GetPatient\GetPatient;
use App\GaelO\UseCases\GetPatient\GetPatientRequest;
use App\GaelO\UseCases\GetPatient\GetPatientResponse;
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

    public function getPatient(int $code=0, GetPatientRequest $getPatientRequest, GetPatientResponse $getPatientResponse, GetPatient $getPatient) {
        $getPatientRequest->code = $code;
        $getPatient->execute($getPatientRequest, $getPatientResponse);
        return response()->json($getPatientResponse->body)
                ->setStatusCode($getPatientResponse->status, $getPatientResponse->statusText);
    }

}
