<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\CreateFileToFormFromTus\CreateFileToFormFromTus;
use App\GaelO\UseCases\CreateFileToFormFromTus\CreateFileToFormFromTusRequest;
use App\GaelO\UseCases\CreateFileToFormFromTus\CreateFileToFormFromTusResponse;
use App\GaelO\UseCases\GetPatientsInStudyFromCenters\GetPatientsInStudyFromCenters;
use App\GaelO\UseCases\GetPatientsInStudyFromCenters\GetPatientsInStudyFromCentersRequest;
use App\GaelO\UseCases\GetPatientsInStudyFromCenters\GetPatientsInStudyFromCentersResponse;
use App\GaelO\UseCases\GetPatientsVisitsInStudy\GetPatientsVisitsInStudy;
use App\GaelO\UseCases\GetPatientsVisitsInStudy\GetPatientsVisitsInStudyRequest;
use App\GaelO\UseCases\GetPatientsVisitsInStudy\GetPatientsVisitsInStudyResponse;
use App\GaelO\UseCases\FindUser\FindUser;
use App\GaelO\UseCases\FindUser\FindUserRequest;
use App\GaelO\UseCases\FindUser\FindUserResponse;
use App\GaelO\UseCases\GetReadiness\GetReadiness;
use App\GaelO\UseCases\GetReadiness\GetReadinessRequest;
use App\GaelO\UseCases\GetReadiness\GetReadinessResponse;
use App\GaelO\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ToolsController extends Controller
{

    public function getPatientsInStudyFromCenters(Request $request,
        GetPatientsInStudyFromCenters $getPatientsInStudyFromCenters,
        GetPatientsInStudyFromCentersRequest $getPatientsInStudyFromCentersRequest,
        GetPatientsInStudyFromCentersResponse $getPatientsInStudyFromCentersResponse) {

        $currentUser = Auth::user();
        $requestData = $request->all();
        
        Util::fillObject($requestData, $getPatientsInStudyFromCentersRequest);
        $getPatientsInStudyFromCentersRequest->currentUserId = $currentUser['id'];

        $getPatientsInStudyFromCenters->execute($getPatientsInStudyFromCentersRequest, $getPatientsInStudyFromCentersResponse);
        return $this->getJsonResponse($getPatientsInStudyFromCentersResponse->body, $getPatientsInStudyFromCentersResponse->status, $getPatientsInStudyFromCentersResponse->statusText);
    }

    public function getPatientsVisitsInStudy(Request $request,
        GetPatientsVisitsInStudy $getPatientsVisitsInStudy,
        GetPatientsVisitsInStudyRequest $getPatientsVisitsInStudyRequest,
        GetPatientsVisitsInStudyResponse $getPatientsVisitsInStudyResponse) {

        $currentUser = Auth::user();     
        $requestData = $request->all();
        $queryParam = $request->query();

        Util::fillObject($requestData, $getPatientsVisitsInStudyRequest);
        $getPatientsVisitsInStudyRequest->currentUserId = $currentUser['id'];
        $getPatientsVisitsInStudyRequest->studyName = $queryParam['studyName'];

        $getPatientsVisitsInStudy->execute($getPatientsVisitsInStudyRequest, $getPatientsVisitsInStudyResponse);
        return $this->getJsonResponse($getPatientsVisitsInStudyResponse->body, $getPatientsVisitsInStudyResponse->status, $getPatientsVisitsInStudyResponse->statusText);
    }

    public function findUser(Request $request,
        FindUser $findUser,
        FindUserRequest $findUserRequest,
        FindUserResponse $findUserResponse) {
            
        $currentUser = Auth::user();
        $requestData = $request->all();
        $queryParam = $request->query();

        Util::fillObject($requestData, $findUserRequest);
        $findUserRequest->currentUserId = $currentUser['id'];
        $findUserRequest->studyName = $queryParam['studyName'];

        $findUser->execute($findUserRequest, $findUserResponse);
        return $this->getJsonResponse($findUserResponse->body, $findUserResponse->status, $findUserResponse->statusText);

    }

    public function readiness(GetReadiness $getReadiness, GetReadinessRequest $getReadinessRequest, GetReadinessResponse $getReadinessResponse)
    {
        $getReadiness->execute($getReadinessRequest, $getReadinessResponse);
        return $this->getJsonResponse($getReadinessResponse->body, $getReadinessResponse->status, $getReadinessResponse->statusText);
    }

    public function createFormFileFromTus(Request $request, CreateFileToFormFromTus $createFileToFormFromTus, CreateFileToFormFromTusRequest $createFileToFormFromTusRequest, CreateFileToFormFromTusResponse $createFileToFormFromTusResponse)
    {
        $currentUser = Auth::user();
        $requestData = $request->all();

        $createFileToFormFromTusRequest = Util::fillObject($requestData, $createFileToFormFromTusRequest);
        $createFileToFormFromTusRequest->currentUserId = $currentUser['id'];
        $createFileToFormFromTus->execute($createFileToFormFromTusRequest, $createFileToFormFromTusResponse);

        return $this->getJsonResponse($createFileToFormFromTusResponse->body, $createFileToFormFromTusResponse->status, $createFileToFormFromTusResponse->statusText);
    }
}
