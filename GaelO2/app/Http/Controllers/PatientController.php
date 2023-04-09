<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\CreatePatientTags\CreatePatientTags;
use App\GaelO\UseCases\CreatePatientTags\CreatePatientTagsRequest;
use App\GaelO\UseCases\CreatePatientTags\CreatePatientTagsResponse;
use App\GaelO\UseCases\DeletePatientTags\DeletePatientTags;
use App\GaelO\UseCases\DeletePatientTags\DeletePatientTagsRequest;
use App\GaelO\UseCases\DeletePatientTags\DeletePatientTagsResponse;
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

use App\GaelO\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PatientController extends Controller
{

    public function getPatient(Request $request, GetPatientRequest $getPatientRequest, GetPatientResponse $getPatientResponse, GetPatient $getPatient, string $id)
    {
        $currentUser = Auth::user();
        $queryParam = $request->query();
        $getPatientRequest->role = $queryParam['role'];
        $getPatientRequest->currentUserId = $currentUser['id'];
        $getPatientRequest->id = $id;
        $getPatientRequest->studyName = $queryParam['studyName'];
        $getPatient->execute($getPatientRequest, $getPatientResponse);
        return $this->getJsonResponse($getPatientResponse->body, $getPatientResponse->status, $getPatientResponse->statusText);
    }

    public function getPatientVisit(Request $request, GetPatientVisit $getPatientVisit, GetPatientVisitRequest $getPatientVisitRequest, GetPatientVisitResponse $getPatientVisitResponse, string $patientId)
    {
        $currentUser = Auth::user();
        $queryParam = $request->query();
        $getPatientVisitRequest->role = $queryParam['role'];
        $getPatientVisitRequest->withTrashed =  array_key_exists('withTrashed', $queryParam);
        $getPatientVisitRequest->currentUserId = $currentUser['id'];
        $getPatientVisitRequest->patientId = $patientId;
        $getPatientVisitRequest->studyName = $queryParam['studyName'];

        $getPatientVisit->execute($getPatientVisitRequest, $getPatientVisitResponse);

        return $this->getJsonResponse($getPatientVisitResponse->body, $getPatientVisitResponse->status, $getPatientVisitResponse->statusText);
    }

    public function modifyPatient(Request $request, ModifyPatient $modifyPatient, ModifyPatientRequest $modifyPatientRequest, ModifyPatientResponse $modifyPatientResponse, string $patientId)
    {
        $currentUser = Auth::user();
        $requestData = $request->all();
        $queryParam = $request->query();

        $modifyPatientRequest = Util::fillObject($requestData, $modifyPatientRequest);
        $modifyPatientRequest->studyName = $queryParam['studyName'];
        $modifyPatientRequest->patientId = $patientId;
        $modifyPatientRequest->currentUserId = $currentUser['id'];
        $modifyPatient->execute($modifyPatientRequest, $modifyPatientResponse);

        return $this->getJsonResponse($modifyPatientResponse->body, $modifyPatientResponse->status, $modifyPatientResponse->statusText);
    }

    public function getCreatableVisits(GetCreatableVisits $getCreatableVisits, GetCreatableVisitsRequest $getCreatableVisitsRequest, GetCreatableVisitsResponse $getCreatableVisitsResponse, string $patientId)
    {

        $currentUser = Auth::user();

        $getCreatableVisitsRequest->currentUserId = $currentUser['id'];
        $getCreatableVisitsRequest->patientId = $patientId;

        $getCreatableVisits->execute($getCreatableVisitsRequest, $getCreatableVisitsResponse);

        return $this->getJsonResponse($getCreatableVisitsResponse->body, $getCreatableVisitsResponse->status, $getCreatableVisitsResponse->statusText);
    }

    public function addPatientTags(Request $request, CreatePatientTags $createPatientTags, CreatePatientTagsRequest $createPatientTagsRequest, CreatePatientTagsResponse $createPatientTagsResponse, string $patientId)
    {

        $currentUser = Auth::user();
        $requestData = $request->all();
        $queryParam = $request->query();
        $createPatientTagsRequest = Util::fillObject($requestData, $createPatientTagsRequest);
        $createPatientTagsRequest->studyName = $queryParam['studyName'];
        $createPatientTagsRequest->patientId = $patientId;
        $createPatientTagsRequest->currentUserId = $currentUser['id'];

        $createPatientTags->execute($createPatientTagsRequest, $createPatientTagsResponse);

        return $this->getJsonResponse($createPatientTagsResponse->body, $createPatientTagsResponse->status, $createPatientTagsResponse->statusText);

    }

    public function deletePatientTags(Request $request, DeletePatientTags $deletePatientTags, DeletePatientTagsRequest $deletePatientTagsRequest, DeletePatientTagsResponse $deletePatientTagsResponse, string $patientId, string $tagName)
    {
        $currentUser = Auth::user();
        $queryParam = $request->query();

        $deletePatientTagsRequest->tag = $tagName;
        $deletePatientTagsRequest->studyName = $queryParam['studyName'];
        $deletePatientTagsRequest->patientId = $patientId;
        $deletePatientTagsRequest->currentUserId = $currentUser['id'];

        $deletePatientTags->execute($deletePatientTagsRequest, $deletePatientTagsResponse);

        return $this->getJsonResponse($deletePatientTagsResponse->body, $deletePatientTagsResponse->status, $deletePatientTagsResponse->statusText);

    }
}
