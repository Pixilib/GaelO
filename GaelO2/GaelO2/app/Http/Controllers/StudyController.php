<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\CreateStudy\CreateStudy;
use App\GaelO\UseCases\CreateStudy\CreateStudyRequest;
use App\GaelO\UseCases\CreateStudy\CreateStudyResponse;
use App\GaelO\UseCases\DeleteStudy\DeleteStudy;
use App\GaelO\UseCases\DeleteStudy\DeleteStudyRequest;
use App\GaelO\UseCases\DeleteStudy\DeleteStudyResponse;
use App\GaelO\UseCases\ExportStudyData\ExportStudyData;
use App\GaelO\UseCases\ExportStudyData\ExportStudyDataRequest;
use App\GaelO\UseCases\ExportStudyData\ExportStudyDataResponse;
use App\GaelO\UseCases\GetInvestigatorFormsFromVisitType\GetInvestigatorFormsFromVisitType;
use App\GaelO\UseCases\GetInvestigatorFormsFromVisitType\GetInvestigatorFormsFromVisitTypeRequest;
use App\GaelO\UseCases\GetInvestigatorFormsFromVisitType\GetInvestigatorFormsFromVisitTypeResponse;
use App\GaelO\UseCases\GetKnownOrthancID\GetKnownOrthancID;
use App\GaelO\UseCases\GetKnownOrthancID\GetKnownOrthancIDRequest;
use App\GaelO\UseCases\GetKnownOrthancID\GetKnownOrthancIDResponse;
use App\GaelO\UseCases\GetPatientFromStudy\GetPatientFromStudy;
use App\GaelO\UseCases\GetPatientFromStudy\GetPatientFromStudyRequest;
use App\GaelO\UseCases\GetPatientFromStudy\GetPatientFromStudyResponse;
use App\GaelO\UseCases\GetPossibleUpload\GetPossibleUpload;
use App\GaelO\UseCases\GetPossibleUpload\GetPossibleUploadRequest;
use App\GaelO\UseCases\GetPossibleUpload\GetPossibleUploadResponse;
use App\GaelO\UseCases\GetReviewProgression\GetReviewProgression;
use App\GaelO\UseCases\GetReviewProgression\GetReviewProgressionRequest;
use App\GaelO\UseCases\GetReviewProgression\GetReviewProgressionResponse;
use App\GaelO\UseCases\GetReviewsFromVisitType\GetReviewsFromVisitType;
use App\GaelO\UseCases\GetReviewsFromVisitType\GetReviewsFromVisitTypeRequest;
use App\GaelO\UseCases\GetReviewsFromVisitType\GetReviewsFromVisitTypeResponse;
use App\GaelO\UseCases\GetStudy\GetStudy;
use App\GaelO\UseCases\GetStudy\GetStudyRequest;
use App\GaelO\UseCases\GetStudy\GetStudyResponse;
use App\GaelO\UseCases\GetStudyDetails\GetStudyDetails;
use App\GaelO\UseCases\GetStudyDetails\GetStudyDetailsRequest;
use App\GaelO\UseCases\GetStudyDetails\GetStudyDetailsResponse;
use App\GaelO\UseCases\GetStudyDetailsSupervisor\GetStudyDetailsSupervisor;
use App\GaelO\UseCases\GetStudyDetailsSupervisor\GetStudyDetailsSupervisorRequest;
use App\GaelO\UseCases\GetStudyDetailsSupervisor\GetStudyDetailsSupervisorResponse;
use App\GaelO\UseCases\GetVisitsFromVisitType\GetVisitsFromVisitType;
use App\GaelO\UseCases\GetVisitsFromVisitType\GetVisitsFromVisitTypeRequest;
use App\GaelO\UseCases\GetVisitsFromVisitType\GetVisitsFromVisitTypeResponse;
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

        if($createStudyResponse->body){
            return response()->json($createStudyResponse->body)
            ->setStatusCode($createStudyResponse->status, $createStudyResponse->statusText);
        }else{
            return response()->noContent()
            ->setStatusCode($createStudyResponse->status, $createStudyResponse->statusText);
        }


    }

    public function getStudies(Request $request, GetStudy $getStudy, GetStudyRequest $getStudyRequest, GetStudyResponse $getStudyResponse, GetStudyDetails $getStudyDetails, GetStudyDetailsRequest $getStudyDetailsRequest, GetStudyDetailsResponse $getStudyDetailsResponse){
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

    public function getStudyDetails(string $studyName, GetStudyDetailsSupervisor $getStudyDetailsSupervisor, GetStudyDetailsSupervisorRequest $getStudyDetailsSupervisorRequest, GetStudyDetailsSupervisorResponse $getStudyDetailsSupervisorResponse){
        $currentUser = Auth::user();
        $getStudyDetailsSupervisorRequest->currentUserId = $currentUser['id'];
        $getStudyDetailsSupervisorRequest->studyName = $studyName;
        $getStudyDetailsSupervisor->execute($getStudyDetailsSupervisorRequest, $getStudyDetailsSupervisorResponse);
        return response()
            ->json($getStudyDetailsSupervisorResponse->body)
            ->setStatusCode($getStudyDetailsSupervisorResponse->status, $getStudyDetailsSupervisorResponse->statusText);

    }

    public function deleteStudy(String $studyName, Request $request, DeleteStudy $deleteStudy,  DeleteStudyRequest $deleteStudyRequest, DeleteStudyResponse $deleteStudyResponse){
        $currentUser = Auth::user();

        $requestData = $request->all();

        $deleteStudyRequest->currentUserId = $currentUser['id'];
        $deleteStudyRequest->studyName = $studyName;
        $deleteStudyRequest = Util::fillObject($requestData, $deleteStudyRequest);

        $deleteStudy->execute($deleteStudyRequest, $deleteStudyResponse);

        return response()->noContent()
                ->setStatusCode($deleteStudyResponse->status, $deleteStudyResponse->statusText);

    }

    public function reactivateStudy(string $studyName, Request $request, ReactivateStudy $reactivateStudy, ReactivateStudyRequest $reactivateStudyRequest, ReactivateStudyResponse $reactivateStudyResponse){
        $currentUser = Auth::user();
        $requestData = $request->all();


        $reactivateStudyRequest->currentUserId = $currentUser['id'];
        $reactivateStudyRequest->studyName = $studyName;
        $reactivateStudyRequest = Util::fillObject($requestData, $reactivateStudyRequest);
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

    public function getReviewProgression(String $studyName, int $visitTypeId, GetReviewProgression $getReviewProgression, GetReviewProgressionRequest $getReviewProgressionRequest, GetReviewProgressionResponse $getReviewProgressionResponse){
        $currentUser = Auth::user();

        $getReviewProgressionRequest->visitTypeId = $visitTypeId;
        $getReviewProgressionRequest->currentUserId = $currentUser['id'];
        $getReviewProgressionRequest->studyName = $studyName;

        $getReviewProgression->execute($getReviewProgressionRequest, $getReviewProgressionResponse);

        if($getReviewProgressionResponse->body){
            return response()->json($getReviewProgressionResponse->body)
            ->setStatusCode($getReviewProgressionResponse->status, $getReviewProgressionResponse->statusText);
        }else{
            return response()->noContent()
            ->setStatusCode($getReviewProgressionResponse->status, $getReviewProgressionResponse->statusText);
        }
    }

    public function exportStudyData(string $studyName, ExportStudyData $exportStudyData, ExportStudyDataRequest $exportStudyDataRequest, ExportStudyDataResponse $exportStudyDataResponse){
        $currentUser = Auth::user();
        $exportStudyDataRequest->currentUserId = $currentUser['id'];
        $exportStudyDataRequest->studyName = $studyName;

        $exportStudyData->execute($exportStudyDataRequest, $exportStudyDataResponse);

        if($exportStudyDataResponse->status === 200){
            return response()->download($exportStudyDataResponse->zipFile, $exportStudyDataResponse->fileName,
                                            array('Content-Type: application/zip','Content-Length: '. filesize($exportStudyDataResponse->zipFile)))
                            ->deleteFileAfterSend(true);
        }else{
            return response()->noContent()
            ->setStatusCode($exportStudyDataResponse->status, $exportStudyDataResponse->statusText);
        }
    }

    public function getVisitsFromVisitType(string $studyName, int $visitTypeId, GetVisitsFromVisitType $getVisitsFromVisitType, GetVisitsFromVisitTypeRequest $getVisitsFromVisitTypeRequest, GetVisitsFromVisitTypeResponse $getVisitsFromVisitTypeResponse){
        $currentUser = Auth::user();
        $getVisitsFromVisitTypeRequest->currentUserId = $currentUser['id'];
        $getVisitsFromVisitTypeRequest->studyName = $studyName;
        $getVisitsFromVisitTypeRequest->visitTypeId = $visitTypeId;

        $getVisitsFromVisitType->execute($getVisitsFromVisitTypeRequest, $getVisitsFromVisitTypeResponse);

        if($getVisitsFromVisitTypeResponse->body){
            return response()->json($getVisitsFromVisitTypeResponse->body)
            ->setStatusCode($getVisitsFromVisitTypeResponse->status, $getVisitsFromVisitTypeResponse->statusText);
        }else{
            return response()->noContent()
            ->setStatusCode($getVisitsFromVisitTypeResponse->status, $getVisitsFromVisitTypeResponse->statusText);
        }
    }


    public function getReviewsFromVisitType(string $studyName, int $visitTypeId, GetReviewsFromVisitType $getReviewsFromVisitType, GetReviewsFromVisitTypeRequest $getReviewsFromVisitTypeRequest, GetReviewsFromVisitTypeResponse $getReviewsFromVisitTypeResponse){
        $currentUser = Auth::user();
        $getReviewsFromVisitTypeRequest->currentUserId = $currentUser['id'];
        $getReviewsFromVisitTypeRequest->studyName = $studyName;
        $getReviewsFromVisitTypeRequest->visitTypeId = $visitTypeId;

        $getReviewsFromVisitType->execute($getReviewsFromVisitTypeRequest, $getReviewsFromVisitTypeResponse);

        if($getReviewsFromVisitTypeResponse->body){
            return response()->json($getReviewsFromVisitTypeResponse->body)
            ->setStatusCode($getReviewsFromVisitTypeResponse->status, $getReviewsFromVisitTypeResponse->statusText);
        }else{
            return response()->noContent()
            ->setStatusCode($getReviewsFromVisitTypeResponse->status, $getReviewsFromVisitTypeResponse->statusText);
        }
    }

    public function getInvestigatorFromsFromVisitType(string $studyName, int $visitTypeId, GetInvestigatorFormsFromVisitType $getInvestigatorFormsFromVisitType, GetInvestigatorFormsFromVisitTypeRequest $getInvestigatorFormsFromVisitTypeRequest, GetInvestigatorFormsFromVisitTypeResponse $getInvestigatorFormsFromVisitTypeResponse){
        $currentUser = Auth::user();
        $getInvestigatorFormsFromVisitTypeRequest->currentUserId = $currentUser['id'];
        $getInvestigatorFormsFromVisitTypeRequest->studyName = $studyName;
        $getInvestigatorFormsFromVisitTypeRequest->visitTypeId = $visitTypeId;

        $getInvestigatorFormsFromVisitType->execute($getInvestigatorFormsFromVisitTypeRequest, $getInvestigatorFormsFromVisitTypeResponse);

        if($getInvestigatorFormsFromVisitTypeResponse->body){
            return response()->json($getInvestigatorFormsFromVisitTypeResponse->body)
            ->setStatusCode($getInvestigatorFormsFromVisitTypeResponse->status, $getInvestigatorFormsFromVisitTypeResponse->statusText);
        }else{
            return response()->noContent()
            ->setStatusCode($getInvestigatorFormsFromVisitTypeResponse->status, $getInvestigatorFormsFromVisitTypeResponse->statusText);
        }
    }





}
