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
use App\GaelO\UseCases\GetDicomsStudiesFromVisitType\GetDicomsStudiesFromVisitType;
use App\GaelO\UseCases\GetDicomsStudiesFromVisitType\GetDicomsStudiesFromVisitTypeRequest;
use App\GaelO\UseCases\GetDicomsStudiesFromVisitType\GetDicomsStudiesFromVisitTypeResponse;
use App\GaelO\UseCases\GetInvestigatorFormsFromVisitType\GetInvestigatorFormsFromVisitType;
use App\GaelO\UseCases\GetInvestigatorFormsFromVisitType\GetInvestigatorFormsFromVisitTypeRequest;
use App\GaelO\UseCases\GetInvestigatorFormsFromVisitType\GetInvestigatorFormsFromVisitTypeResponse;
use App\GaelO\UseCases\GetInvestigatorFormsMetadataFromVisitType\GetInvestigatorFormsMetadataFromVisitType;
use App\GaelO\UseCases\GetInvestigatorFormsMetadataFromVisitType\GetInvestigatorFormsMetadataFromVisitTypeRequest;
use App\GaelO\UseCases\GetInvestigatorFormsMetadataFromVisitType\GetInvestigatorFormsMetadataFromVisitTypeResponse;
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
use App\GaelO\UseCases\GetReviewsMetadataFromVisitType\GetReviewsMetadataFromVisitType;
use App\GaelO\UseCases\GetReviewsMetadataFromVisitType\GetReviewsMetadataFromVisitTypeRequest;
use App\GaelO\UseCases\GetReviewsMetadataFromVisitType\GetReviewsMetadataFromVisitTypeResponse;
use App\GaelO\UseCases\GetStudy\GetStudy;
use App\GaelO\UseCases\GetStudy\GetStudyRequest;
use App\GaelO\UseCases\GetStudy\GetStudyResponse;
use App\GaelO\UseCases\GetStudyDetails\GetStudyDetails;
use App\GaelO\UseCases\GetStudyDetails\GetStudyDetailsRequest;
use App\GaelO\UseCases\GetStudyDetails\GetStudyDetailsResponse;
use App\GaelO\UseCases\GetStudyDetailsSupervisor\GetStudyDetailsSupervisor;
use App\GaelO\UseCases\GetStudyDetailsSupervisor\GetStudyDetailsSupervisorRequest;
use App\GaelO\UseCases\GetStudyDetailsSupervisor\GetStudyDetailsSupervisorResponse;
use App\GaelO\UseCases\GetStudyStatistics\GetStudyStatistics;
use App\GaelO\UseCases\GetStudyStatistics\GetStudyStatisticsRequest;
use App\GaelO\UseCases\GetStudyStatistics\GetStudyStatisticsResponse;
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
use App\GaelO\UseCases\Reminder\SendReminder;
use App\GaelO\UseCases\Reminder\ReminderRequest;
use App\GaelO\UseCases\Reminder\ReminderResponse;
use App\GaelO\UseCases\SendMail\SendMail;
use App\GaelO\UseCases\SendMail\SendMailRequest;
use App\GaelO\UseCases\SendMail\SendMailResponse;
use App\GaelO\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudyController extends Controller
{
    public function createStudy(Request $request, CreateStudy $createStudy, CreateStudyRequest $createStudyRequest, CreateStudyResponse $createStudyResponse)
    {

        $currentUser = Auth::user();
        $createStudyRequest->currentUserId = $currentUser['id'];
        $requestData = $request->all();
        $createStudyRequest = Util::fillObject($requestData, $createStudyRequest);

        $createStudy->execute($createStudyRequest, $createStudyResponse);

        return $this->getJsonResponse($createStudyResponse->body, $createStudyResponse->status, $createStudyResponse->statusText);
    }

    public function getStudies(Request $request, GetStudy $getStudy, GetStudyRequest $getStudyRequest, GetStudyResponse $getStudyResponse, GetStudyDetails $getStudyDetails, GetStudyDetailsRequest $getStudyDetailsRequest, GetStudyDetailsResponse $getStudyDetailsResponse)
    {
        $currentUser = Auth::user();
        $queryParam = $request->query();
        if (array_key_exists('expand', $queryParam)) {
            $getStudyDetailsRequest->currentUserId = $currentUser['id'];
            $getStudyDetails->execute($getStudyDetailsRequest, $getStudyDetailsResponse);
            return $this->getJsonResponse($getStudyDetailsResponse->body, $getStudyDetailsResponse->status, $getStudyDetailsResponse->statusText);
        } else {
            $getStudyRequest->currentUserId = $currentUser['id'];
            $getStudyRequest->withTrashed = key_exists('withTrashed', $queryParam);
            $getStudy->execute($getStudyRequest, $getStudyResponse);
            return $this->getJsonResponse($getStudyResponse->body, $getStudyResponse->status, $getStudyResponse->statusText);
        }
    }

    public function getStudyDetails(GetStudyDetailsSupervisor $getStudyDetailsSupervisor, GetStudyDetailsSupervisorRequest $getStudyDetailsSupervisorRequest, GetStudyDetailsSupervisorResponse $getStudyDetailsSupervisorResponse, string $studyName)
    {
        $currentUser = Auth::user();
        $getStudyDetailsSupervisorRequest->currentUserId = $currentUser['id'];
        $getStudyDetailsSupervisorRequest->studyName = $studyName;
        $getStudyDetailsSupervisor->execute($getStudyDetailsSupervisorRequest, $getStudyDetailsSupervisorResponse);
        return $this->getJsonResponse($getStudyDetailsSupervisorResponse->body, $getStudyDetailsSupervisorResponse->status, $getStudyDetailsSupervisorResponse->statusText);
    }

    public function deleteStudy(Request $request, DeleteStudy $deleteStudy,  DeleteStudyRequest $deleteStudyRequest, DeleteStudyResponse $deleteStudyResponse, String $studyName)
    {
        $currentUser = Auth::user();

        $requestData = $request->all();

        $deleteStudyRequest->currentUserId = $currentUser['id'];
        $deleteStudyRequest->studyName = $studyName;
        $deleteStudyRequest = Util::fillObject($requestData, $deleteStudyRequest);

        $deleteStudy->execute($deleteStudyRequest, $deleteStudyResponse);

        return $this->getJsonResponse($deleteStudyResponse->body, $deleteStudyResponse->status, $deleteStudyResponse->statusText);
    }

    public function reactivateStudy(Request $request, ReactivateStudy $reactivateStudy, ReactivateStudyRequest $reactivateStudyRequest, ReactivateStudyResponse $reactivateStudyResponse, string $studyName)
    {
        $currentUser = Auth::user();
        $requestData = $request->all();


        $reactivateStudyRequest->currentUserId = $currentUser['id'];
        $reactivateStudyRequest->studyName = $studyName;
        $reactivateStudyRequest = Util::fillObject($requestData, $reactivateStudyRequest);
        $reactivateStudy->execute($reactivateStudyRequest, $reactivateStudyResponse);
        return $this->getJsonResponse($reactivateStudyResponse->body, $reactivateStudyResponse->status, $reactivateStudyResponse->statusText);
    }

    public function importPatients(Request $request, ImportPatients $importPatients, ImportPatientsRequest $importPatientsRequest, ImportPatientsResponse $importPatientsResponse, string $studyName)
    {

        $currentUser = Auth::user();
        $importPatientsRequest->patients = $request->all();
        $importPatientsRequest->studyName = $studyName;
        $importPatientsRequest->currentUserId = $currentUser['id'];
        $importPatients->execute($importPatientsRequest, $importPatientsResponse);
        return $this->getJsonResponse($importPatientsResponse->body, $importPatientsResponse->status, $importPatientsResponse->statusText);
    }

    public function isKnownOrthancId(GetKnownOrthancID $getKnownOrthancID, GetKnownOrthancIDRequest $getKnownOrthancIDRequest, GetKnownOrthancIDResponse $getKnownOrthancIDResponse, string $studyName, string $orthancStudyID)
    {

        $currentUser = Auth::user();
        $getKnownOrthancIDRequest->currentUserId = $currentUser['id'];
        $getKnownOrthancIDRequest->studyName = $studyName;
        $getKnownOrthancIDRequest->orthancStudyID = $orthancStudyID;

        $getKnownOrthancID->execute($getKnownOrthancIDRequest, $getKnownOrthancIDResponse);

        return $this->getJsonResponse($getKnownOrthancIDResponse->body, $getKnownOrthancIDResponse->status, $getKnownOrthancIDResponse->statusText);
    }

    public function getVisitsTree(Request $request, GetVisitsTree $getVisitsTree, GetVisitsTreeRequest $getVisitsTreeRequest, GetVisitsTreeResponse $getVisitsTreeResponse, string $studyName)
    {
        $currentUser = Auth::user();
        $queryParam = $request->query();
        $getVisitsTreeRequest->currentUserId = $currentUser['id'];
        $getVisitsTreeRequest->role = $queryParam['role'];
        $getVisitsTreeRequest->studyName = $studyName;

        $getVisitsTree->execute($getVisitsTreeRequest, $getVisitsTreeResponse);

        return $this->getJsonResponse($getVisitsTreeResponse->body, $getVisitsTreeResponse->status, $getVisitsTreeResponse->statusText);
    }

    public function getPossibleUploads(GetPossibleUpload $getPossibleUpload, GetPossibleUploadRequest $getPossibleUploadRequest, GetPossibleUploadResponse $getPossibleUploadResponse, string $studyName)
    {
        $currentUser = Auth::user();
        $getPossibleUploadRequest->currentUserId = $currentUser['id'];
        $getPossibleUploadRequest->studyName = $studyName;
        $getPossibleUpload->execute($getPossibleUploadRequest, $getPossibleUploadResponse);
        return $this->getJsonResponse($getPossibleUploadResponse->body, $getPossibleUploadResponse->status, $getPossibleUploadResponse->statusText);
    }

    public function getPatientFromStudy(Request $request, GetPatientFromStudyRequest $getPatientRequest, GetPatientFromStudyResponse $getPatientResponse, GetPatientFromStudy $getPatient, String $studyName)
    {
        $currentUser = Auth::user();
        $queryParam = $request->query();
        $getPatientRequest->role = $queryParam['role'];
        $getPatientRequest->currentUserId = $currentUser['id'];
        $getPatientRequest->studyName = $studyName;
        $getPatient->execute($getPatientRequest, $getPatientResponse);
        return $this->getJsonResponse($getPatientResponse->body, $getPatientResponse->status, $getPatientResponse->statusText);
    }

    public function getReviewProgression(Request $request, GetReviewProgression $getReviewProgression, GetReviewProgressionRequest $getReviewProgressionRequest, GetReviewProgressionResponse $getReviewProgressionResponse, int $visitTypeId)
    {
        $currentUser = Auth::user();

        $queryParam = $request->query();
        $getReviewProgressionRequest->studyName = $queryParam['studyName'];
        $getReviewProgressionRequest->visitTypeId = $visitTypeId;
        $getReviewProgressionRequest->currentUserId = $currentUser['id'];

        $getReviewProgression->execute($getReviewProgressionRequest, $getReviewProgressionResponse);

        return $this->getJsonResponse($getReviewProgressionResponse->body, $getReviewProgressionResponse->status, $getReviewProgressionResponse->statusText);
    }

    public function exportStudyData(ExportStudyData $exportStudyData, ExportStudyDataRequest $exportStudyDataRequest, ExportStudyDataResponse $exportStudyDataResponse, string $studyName)
    {
        $currentUser = Auth::user();
        $exportStudyDataRequest->currentUserId = $currentUser['id'];
        $exportStudyDataRequest->studyName = $studyName;

        $exportStudyData->execute($exportStudyDataRequest, $exportStudyDataResponse);

        if ($exportStudyDataResponse->status === 200) {
            return response()->download(
                $exportStudyDataResponse->zipFile,
                $exportStudyDataResponse->fileName,
                array('Content-Type: application/zip', 'Content-Length: ' . filesize($exportStudyDataResponse->zipFile))
            )
                ->deleteFileAfterSend(true);
        } else {
            return response()->noContent()
                ->setStatusCode($exportStudyDataResponse->status, $exportStudyDataResponse->statusText);
        }
    }

    public function getVisitsFromVisitType(Request $request, GetVisitsFromVisitType $getVisitsFromVisitType, GetVisitsFromVisitTypeRequest $getVisitsFromVisitTypeRequest, GetVisitsFromVisitTypeResponse $getVisitsFromVisitTypeResponse, int $visitTypeId)
    {
        $currentUser = Auth::user();
        $queryParam = $request->query();
        $getVisitsFromVisitTypeRequest->studyName = $queryParam['studyName'];
        $getVisitsFromVisitTypeRequest->currentUserId = $currentUser['id'];
        $getVisitsFromVisitTypeRequest->visitTypeId = $visitTypeId;

        $getVisitsFromVisitType->execute($getVisitsFromVisitTypeRequest, $getVisitsFromVisitTypeResponse);
        return $this->getJsonResponse($getVisitsFromVisitTypeResponse->body, $getVisitsFromVisitTypeResponse->status, $getVisitsFromVisitTypeResponse->statusText);
    }


    public function getReviewsFromVisitType(Request $request, GetReviewsFromVisitType $getReviewsFromVisitType, GetReviewsFromVisitTypeRequest $getReviewsFromVisitTypeRequest, GetReviewsFromVisitTypeResponse $getReviewsFromVisitTypeResponse, int $visitTypeId)
    {
        $currentUser = Auth::user();
        $queryParam = $request->query();
        $getReviewsFromVisitTypeRequest->studyName = $queryParam['studyName'];
        $getReviewsFromVisitTypeRequest->currentUserId = $currentUser['id'];
        $getReviewsFromVisitTypeRequest->visitTypeId = $visitTypeId;

        $getReviewsFromVisitType->execute($getReviewsFromVisitTypeRequest, $getReviewsFromVisitTypeResponse);

        return $this->getJsonResponse($getReviewsFromVisitTypeResponse->body, $getReviewsFromVisitTypeResponse->status, $getReviewsFromVisitTypeResponse->statusText);
    }

    public function getReviewsMetadataFromVisitType(Request $request, GetReviewsMetadataFromVisitType $getReviewsMetadataFromVisitType, GetReviewsMetadataFromVisitTypeRequest $getReviewsMetadataFromVisitTypeRequest, GetReviewsMetadataFromVisitTypeResponse $getReviewsMetadataFromVisitTypeResponse, int $visitTypeId){

        $currentUser = Auth::user();
        $queryParam = $request->query();
        $getReviewsMetadataFromVisitTypeRequest->studyName = $queryParam['studyName'];
        $getReviewsMetadataFromVisitTypeRequest->currentUserId = $currentUser['id'];
        $getReviewsMetadataFromVisitTypeRequest->visitTypeId = $visitTypeId;

        $getReviewsMetadataFromVisitType->execute($getReviewsMetadataFromVisitTypeRequest, $getReviewsMetadataFromVisitTypeResponse);

        return $this->getJsonResponse($getReviewsMetadataFromVisitTypeResponse->body, $getReviewsMetadataFromVisitTypeResponse->status, $getReviewsMetadataFromVisitTypeResponse->statusText);

    }

    public function getInvestigatorFormsFromVisitType(Request $request, GetInvestigatorFormsFromVisitType $getInvestigatorFormsFromVisitType, GetInvestigatorFormsFromVisitTypeRequest $getInvestigatorFormsFromVisitTypeRequest, GetInvestigatorFormsFromVisitTypeResponse $getInvestigatorFormsFromVisitTypeResponse, int $visitTypeId)
    {
        $currentUser = Auth::user();
        $queryParam = $request->query();
        $getInvestigatorFormsFromVisitTypeRequest->studyName = $queryParam['studyName'];
        $getInvestigatorFormsFromVisitTypeRequest->currentUserId = $currentUser['id'];
        $getInvestigatorFormsFromVisitTypeRequest->visitTypeId = $visitTypeId;

        $getInvestigatorFormsFromVisitType->execute($getInvestigatorFormsFromVisitTypeRequest, $getInvestigatorFormsFromVisitTypeResponse);

        return $this->getJsonResponse($getInvestigatorFormsFromVisitTypeResponse->body, $getInvestigatorFormsFromVisitTypeResponse->status, $getInvestigatorFormsFromVisitTypeResponse->statusText);
    }

    public function getInvestigatorFormsMetadataFromVisitType(Request $request, GetInvestigatorFormsMetadataFromVisitType $getInvestigatorFormsMetadataFromVisitType, GetInvestigatorFormsMetadataFromVisitTypeRequest $getInvestigatorFormsMetadataFromVisitTypeRequest, GetInvestigatorFormsMetadataFromVisitTypeResponse $getInvestigatorFormsMetadataFromVisitTypeResponse, int $visitTypeId){
        $currentUser = Auth::user();
        $queryParam = $request->query();
        $getInvestigatorFormsMetadataFromVisitTypeRequest->studyName = $queryParam['studyName'];
        $getInvestigatorFormsMetadataFromVisitTypeRequest->currentUserId = $currentUser['id'];
        $getInvestigatorFormsMetadataFromVisitTypeRequest->visitTypeId = $visitTypeId;

        $getInvestigatorFormsMetadataFromVisitType->execute($getInvestigatorFormsMetadataFromVisitTypeRequest, $getInvestigatorFormsMetadataFromVisitTypeResponse);

        return $this->getJsonResponse($getInvestigatorFormsMetadataFromVisitTypeResponse->body, $getInvestigatorFormsMetadataFromVisitTypeResponse->status, $getInvestigatorFormsMetadataFromVisitTypeResponse->statusText);
    }

    public function getDicomStudiesFromVisitType(Request $request, GetDicomsStudiesFromVisitType $getDicomsStudiesFromVisitType, GetDicomsStudiesFromVisitTypeRequest $getDicomsStudiesFromVisitTypeRequest, GetDicomsStudiesFromVisitTypeResponse $getDicomsStudiesFromVisitTypeResponse, int $visitTypeId)
    {

        $currentUser = Auth::user();
        $queryParam = $request->query();
        $getDicomsStudiesFromVisitTypeRequest->currentUserId = $currentUser['id'];
        $getDicomsStudiesFromVisitTypeRequest->studyName = $queryParam['studyName'];
        $getDicomsStudiesFromVisitTypeRequest->visitTypeId = $visitTypeId;
        $getDicomsStudiesFromVisitTypeRequest->withTrashed = key_exists('withTrashed', $queryParam);

        $getDicomsStudiesFromVisitType->execute($getDicomsStudiesFromVisitTypeRequest, $getDicomsStudiesFromVisitTypeResponse);

        return $this->getJsonResponse($getDicomsStudiesFromVisitTypeResponse->body, $getDicomsStudiesFromVisitTypeResponse->status, $getDicomsStudiesFromVisitTypeResponse->statusText);
    }

    public function sendReminder(Request $request, SendReminder $sendReminder, ReminderRequest $reminderRequest, ReminderResponse $reminderResponse, string $studyName) {
        $currentUser = Auth::user();
        $requestData = $request->all();
        $reminderRequest->currentUserId = $currentUser['id'];
        $reminderRequest->study = $studyName;
        $reminderRequest = Util::fillObject($requestData, $reminderRequest);
        $sendReminder->execute($reminderRequest, $reminderResponse);
        return $this->getJsonResponse($reminderResponse->body, $reminderResponse->status, $reminderResponse->statusText);
    }

    public function sendMail(Request $request, SendMail $sendMail, SendMailRequest $sendMailRequest, SendMailResponse $sendMailResponse) {
        $currentUser = Auth::user();
        $requestData = $request->all();
        $sendMailRequest->currentUserId = $currentUser['id'];
        $sendMailRequest = Util::fillObject($requestData, $sendMailRequest);
        $sendMail->execute($sendMailRequest, $sendMailResponse);
        return $this->getJsonResponse($sendMailResponse->body, $sendMailResponse->status, $sendMailResponse->statusText);
    }

    public function getStudyStatistics(GetStudyStatistics $getStudyStatistics, GetStudyStatisticsRequest $getStudyStatisticsRequest, GetStudyStatisticsResponse $getStudyStatisticsResponse, string $studyName){

        $currentUser = Auth::user();

        $getStudyStatisticsRequest->currentUserId = $currentUser['id'];
        $getStudyStatisticsRequest->studyName = $studyName;
        $getStudyStatistics->execute($getStudyStatisticsRequest, $getStudyStatisticsResponse);
        return $this->getJsonResponse($getStudyStatisticsResponse->body, $getStudyStatisticsResponse->status, $getStudyStatisticsResponse->statusText);

    }
}
