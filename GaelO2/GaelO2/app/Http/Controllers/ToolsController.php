<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\GetPatientsInStudyFromCenters\GetPatientsInStudyFromCenters;
use App\GaelO\UseCases\GetPatientsInStudyFromCenters\GetPatientsInStudyFromCentersRequest;
use App\GaelO\UseCases\GetPatientsInStudyFromCenters\GetPatientsInStudyFromCentersResponse;
use App\GaelO\UseCases\GetPatientsVisitsInStudy\GetPatientsVisitsInStudy;
use App\GaelO\UseCases\GetPatientsVisitsInStudy\GetPatientsVisitsInStudyRequest;
use App\GaelO\UseCases\GetPatientsVisitsInStudy\GetPatientsVisitsInStudyResponse;
use App\GaelO\UseCases\GetCentersFromStudy\GetCentersFromStudy;
use App\GaelO\UseCases\GetCentersFromStudy\GetCentersFromStudyRequest;
use App\GaelO\UseCases\GetCentersFromStudy\GetCentersFromStudyResponse;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ToolsController extends Controller
{

    public function getCentersFromStudy(String $studyName,
        GetCentersFromStudy $getCentersFromStudy,
        GetCentersFromStudyRequest $getCentersFromStudyRequest,
        GetCentersFromStudyResponse $getCentersFromStudyResponse) {

        $curentUser = Auth::user();
        $getCentersFromStudyRequest->currentUserId = $curentUser['id'];
        $getCentersFromStudyRequest->studyName = $studyName;

        $getCentersFromStudy->execute($getCentersFromStudyRequest, $getCentersFromStudyResponse);
        return $this->getJsonResponse($getCentersFromStudyResponse->body, $getCentersFromStudyResponse->status, $getCentersFromStudyResponse->statusText);
    }

    public function getPatientsInStudyFromCenters(String $studyName, Request $request,
        GetPatientsInStudyFromCenters $getPatientsInStudyFromCenters,
        GetPatientsInStudyFromCentersRequest $getPatientsInStudyFromCentersRequest,
        GetPatientsInStudyFromCentersResponse $getPatientsInStudyFromCentersResponse) {

        $curentUser = Auth::user();
        $getPatientsInStudyFromCentersRequest->currentUserId = $curentUser['id'];
        $getPatientsInStudyFromCentersRequest->studyName = $studyName;
        $getPatientsInStudyFromCentersRequest->centerCodes = $request->all();

        $getPatientsInStudyFromCenters->execute($getPatientsInStudyFromCentersRequest, $getPatientsInStudyFromCentersResponse);
        return $this->getJsonResponse($getPatientsInStudyFromCentersResponse->body, $getPatientsInStudyFromCentersResponse->status, $getPatientsInStudyFromCentersResponse->statusText);
    }

    public function getPatientsVisitsInStudy(String $studyName, Request $request,
        GetPatientsVisitsInStudy $getPatientsVisitsInStudy,
        GetPatientsVisitsInStudyRequest $getPatientsVisitsInStudyRequest,
        GetPatientsVisitsInStudyResponse $getPatientsVisitsInStudyResponse) {

        $curentUser = Auth::user();

        $getPatientsVisitsInStudyRequest->currentUserId = $curentUser['id'];
        $getPatientsVisitsInStudyRequest->studyName = $studyName;
        $getPatientsVisitsInStudyRequest->patientCodes = $request->all();

        $getPatientsVisitsInStudy->execute($getPatientsVisitsInStudyRequest, $getPatientsVisitsInStudyResponse);
        return $this->getJsonResponse($getPatientsVisitsInStudyResponse->body, $getPatientsVisitsInStudyResponse->status, $getPatientsVisitsInStudyResponse->statusText);
    }
}
