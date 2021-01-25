<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\CreateStudy\CreateStudy;
use App\GaelO\UseCases\CreateStudy\CreateStudyRequest;
use App\GaelO\UseCases\CreateStudy\CreateStudyResponse;
use App\GaelO\UseCases\DeleteStudy\DeleteStudy;
use App\GaelO\UseCases\DeleteStudy\DeleteStudyQuery;
use App\GaelO\UseCases\DeleteStudy\DeleteStudyRequest;
use App\GaelO\UseCases\DeleteStudy\DeleteStudyResponse;
use App\GaelO\UseCases\GetKnownOrthancID\GetKnownOrthancID;
use App\GaelO\UseCases\GetKnownOrthancID\GetKnownOrthancIDRequest;
use App\GaelO\UseCases\GetKnownOrthancID\GetKnownOrthancIDResponse;
use App\GaelO\UseCases\GetPatientFromStudy\GetPatientFromStudy;
use App\GaelO\UseCases\GetPatientFromStudy\GetPatientFromStudyRequest;
use App\GaelO\UseCases\GetPatientFromStudy\GetPatientFromStudyResponse;
use App\GaelO\UseCases\GetPossibleUpload\GetPossibleUpload;
use App\GaelO\UseCases\GetPossibleUpload\GetPossibleUploadRequest;
use App\GaelO\UseCases\GetPossibleUpload\GetPossibleUploadResponse;
use App\GaelO\UseCases\GetStudy\GetStudy;
use App\GaelO\UseCases\GetStudy\GetStudyRequest;
use App\GaelO\UseCases\GetStudy\GetStudyResponse;
use App\GaelO\UseCases\GetStudyDetails\GetStudyDetails;
use App\GaelO\UseCases\GetStudyDetails\GetStudyDetailsRequest;
use App\GaelO\UseCases\GetStudyDetails\GetStudyDetailsResponse;
use App\GaelO\UseCases\GetVisitsTree\GetVisitsTree;
use App\GaelO\UseCases\GetVisitsTree\GetVisitsTreeRequest;
use App\GaelO\UseCases\GetVisitsTree\GetVisitsTreeResponse;
use App\GaelO\UseCases\ImportPatients\ImportPatients;
use App\GaelO\UseCases\ImportPatients\ImportPatientsRequest;
use App\GaelO\UseCases\ImportPatients\ImportPatientsResponse;
use App\GaelO\UseCases\ReactivateStudy\ReactivateStudy;
use App\GaelO\UseCases\ReactivateStudy\ReactivateStudyRequest;
use App\GaelO\UseCases\ReactivateStudy\ReactivateStudyResponse;
use App\GaelO\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class StudyController extends Controller
{
    public function createStudy(Request $request, CreateStudy $createStudy, CreateStudyRequest $createStudyRequest, CreateStudyResponse $createStudyResponse){

        $currentUser = Auth::user();
        $createStudyRequest->currentUserId = $currentUser['id'];
        $requestData = $request->all();
        $createStudyRequest = Util::fillObject($requestData, $createStudyRequest);

        $createStudy->execute($createStudyRequest, $createStudyResponse);

        return response()->noContent()
                ->setStatusCode($createStudyResponse->status, $createStudyResponse->statusText);

    }

    public function getStudy(Request $request, GetStudy $getStudy, GetStudyRequest $getStudyRequest, GetStudyResponse $getStudyResponse, GetStudyDetails $getStudyDetails, GetStudyDetailsRequest $getStudyDetailsRequest, GetStudyDetailsResponse $getStudyDetailsResponse){
        $currentUser = Auth::user();
        $queryParam = $request->query();
        if(array_key_exists('expand', $queryParam) ){
            $getStudyDetailsRequest->currentUserId = $currentUser['id'];
            $getStudyDetails->execute($getStudyDetailsRequest, $getStudyDetailsResponse);
            return response()->json($getStudyDetailsResponse->body)
            ->setStatusCode($getStudyDetailsResponse->status, $getStudyDetailsResponse->statusText);
        }else {
            $getStudyRequest->currentUserId = $currentUser['id'];
            $getStudy->execute($getStudyRequest, $getStudyResponse);
            return response()->json($getStudyResponse->body)
            ->setStatusCode($getStudyResponse->status, $getStudyResponse->statusText);
        }

    }

    public function deleteStudy(String $studyName, DeleteStudy $deleteStudy,  DeleteStudyRequest $deleteStudyRequest, DeleteStudyResponse $deleteStudyResponse){
        $currentUser = Auth::user();
        $deleteStudyRequest->currentUserId = $currentUser['id'];
        $deleteStudyRequest->studyName = $studyName;
        $deleteStudy->execute($deleteStudyRequest, $deleteStudyResponse);

        return response()->noContent()
                ->setStatusCode($deleteStudyResponse->status, $deleteStudyResponse->statusText);

    }

    public function reactivateStudy(string $studyName, ReactivateStudy $reactivateStudy, ReactivateStudyRequest $reactivateStudyRequest, ReactivateStudyResponse $reactivateStudyResponse){
        $currentUser = Auth::user();
        $reactivateStudyRequest->currentUserId = $currentUser['id'];
        $reactivateStudyRequest->studyName = $studyName;
        $reactivateStudy->execute($reactivateStudyRequest, $reactivateStudyResponse);
        return response()->noContent()
                ->setStatusCode($reactivateStudyResponse->status, $reactivateStudyResponse->statusText);
    }

    public function importPatients(string $studyName, Request $request, ImportPatients $importPatients, ImportPatientsRequest $importPatientsRequest, ImportPatientsResponse $importPatientsResponse){

        $currentUser = Auth::user();
        $importPatientsRequest->patients = $request->all() ;
        $importPatientsRequest->studyName = $studyName;
        $importPatientsRequest->currentUserId = $currentUser['id'];
        $importPatients->execute($importPatientsRequest, $importPatientsResponse);

        return response()->json($importPatientsResponse->body)->setStatusCode($importPatientsResponse->status, $importPatientsResponse->statusText);
    }

    public function isKnownOrthancId(string $studyName, string $orthancStudyID, GetKnownOrthancID $getKnownOrthancID, GetKnownOrthancIDRequest $getKnownOrthancIDRequest, GetKnownOrthancIDResponse $getKnownOrthancIDResponse){

        $currentUser = Auth::user();
        $getKnownOrthancIDRequest->currentUserId = $currentUser['id'];
        $getKnownOrthancIDRequest->studyName = $studyName;
        $getKnownOrthancIDRequest->orthancStudyID = $orthancStudyID;

        $getKnownOrthancID->execute($getKnownOrthancIDRequest, $getKnownOrthancIDResponse);
        return response()->json($getKnownOrthancIDResponse->body)->setStatusCode($getKnownOrthancIDResponse->status, $getKnownOrthancIDResponse->statusText);

    }

    public function getVisitsTree(string $studyName, Request $request, GetVisitsTree $getVisitsTree, GetVisitsTreeRequest $getVisitsTreeRequest, GetVisitsTreeResponse $getVisitsTreeResponse){
        $currentUser = Auth::user();
        $queryParam = $request->query();
        $getVisitsTreeRequest->currentUserId = $currentUser['id'];
        $getVisitsTreeRequest->role = $queryParam['role'];
        $getVisitsTreeRequest->studyName = $studyName;

        $getVisitsTree->execute($getVisitsTreeRequest, $getVisitsTreeResponse);

        return response()->json($getVisitsTreeResponse->body)->setStatusCode($getVisitsTreeResponse->status, $getVisitsTreeResponse->statusText);
    }

    public function getPossibleUploads(string $studyName, GetPossibleUpload $getPossibleUpload, GetPossibleUploadRequest $getPossibleUploadRequest, GetPossibleUploadResponse $getPossibleUploadResponse){
        $currentUser = Auth::user();
        $getPossibleUploadRequest->currentUserId = $currentUser['id'];
        $getPossibleUploadRequest->studyName = $studyName;
        $getPossibleUpload->execute($getPossibleUploadRequest, $getPossibleUploadResponse);

        return response()->json($getPossibleUploadResponse->body)->setStatusCode($getPossibleUploadResponse->status, $getPossibleUploadResponse->statusText);

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

}
