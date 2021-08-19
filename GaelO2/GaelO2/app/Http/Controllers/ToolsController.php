<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\GetPatientsInStudyFromCenters\GetPatientsInStudyFromCenters;
use App\GaelO\UseCases\GetPatientsInStudyFromCenters\GetPatientsInStudyFromCentersRequest;
use App\GaelO\UseCases\GetPatientsInStudyFromCenters\GetPatientsInStudyFromCentersResponse;
use App\GaelO\UseCases\GetPatientsVisitsInStudy\GetPatientsVisitsInStudy;
use App\GaelO\UseCases\GetPatientsVisitsInStudy\GetPatientsVisitsInStudyRequest;
use App\GaelO\UseCases\GetPatientsVisitsInStudy\GetPatientsVisitsInStudyResponse;
use App\GaelO\UseCases\GetVisitTypesDetails\GetVisitTypesDetails;
use App\GaelO\UseCases\GetVisitTypesDetails\GetVisitTypesDetailsRequest;
use App\GaelO\UseCases\GetVisitTypesDetails\GetVisitTypesDetailsResponse;
use App\GaelO\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ToolsController extends Controller
{

    public function getPatientsInStudyFromCenters(Request $request,
        GetPatientsInStudyFromCenters $getPatientsInStudyFromCenters,
        GetPatientsInStudyFromCentersRequest $getPatientsInStudyFromCentersRequest,
        GetPatientsInStudyFromCentersResponse $getPatientsInStudyFromCentersResponse) {

        $curentUser = Auth::user();
        $getPatientsInStudyFromCentersRequest->currentUserId = $curentUser['id'];
        $requestData = $request->all();
        $getPatientsInStudyFromCentersRequest = Util::fillObject($requestData, $getPatientsInStudyFromCentersRequest);

        $getPatientsInStudyFromCenters->execute($getPatientsInStudyFromCentersRequest, $getPatientsInStudyFromCentersResponse);
        return $this->getJsonResponse($getPatientsInStudyFromCentersResponse->body, $getPatientsInStudyFromCentersResponse->status, $getPatientsInStudyFromCentersResponse->statusText);
    }

    public function getPatientsVisitsInStudy(Request $request,
        GetPatientsVisitsInStudy $getPatientsVisitsInStudy,
        GetPatientsVisitsInStudyRequest $getPatientsVisitsInStudyRequest,
        GetPatientsVisitsInStudyResponse $getPatientsVisitsInStudyResponse) {

        $curentUser = Auth::user();

        $getPatientsVisitsInStudyRequest->currentUserId = $curentUser['id'];
        $requestData = $request->all();
        $getPatientsVisitsInStudyRequest = Util::fillObject($requestData, $getPatientsVisitsInStudyRequest);

        $getPatientsVisitsInStudy->execute($getPatientsVisitsInStudyRequest, $getPatientsVisitsInStudyResponse);
        return $this->getJsonResponse($getPatientsVisitsInStudyResponse->body, $getPatientsVisitsInStudyResponse->status, $getPatientsVisitsInStudyResponse->statusText);
    }

    public function getVisitTypesDetails(Request $request,
        GetVisitTypesDetails $getVisitTypesDetails,
        GetVisitTypesDetailsRequest $getVisitTypesDetailsRequest,
        GetVisitTypesDetailsResponse $getVisitTypesDetailsResponse) {

        $curentUser = Auth::user();

        $getVisitTypesDetailsRequest->currentUserId = $curentUser['id'];
        $requestData = $request->all();
        $getVisitTypesDetailsRequest = Util::fillObject($requestData, $getVisitTypesDetailsRequest);

        $getVisitTypesDetails->execute($getVisitTypesDetailsRequest, $getVisitTypesDetailsResponse);
        return $this->getJsonResponse($getVisitTypesDetailsResponse->body, $getVisitTypesDetailsResponse->status, $getVisitTypesDetailsResponse->statusText);
    }

}
