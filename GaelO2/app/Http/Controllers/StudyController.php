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
use App\GaelO\UseCases\GetCreatablePatients\GetCreatablePatients;
use App\GaelO\UseCases\GetCreatablePatients\GetCreatablePatientsRequest;
use App\GaelO\UseCases\GetCreatablePatients\GetCreatablePatientsResponse;
use App\GaelO\UseCases\GetDicomsStudiesFromStudy\GetDicomsStudiesFromStudy;
use App\GaelO\UseCases\GetDicomsStudiesFromStudy\GetDicomsStudiesFromStudyRequest;
use App\GaelO\UseCases\GetDicomsStudiesFromStudy\GetDicomsStudiesFromStudyResponse;
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
use App\GaelO\UseCases\GetStudyReviewProgression\GetStudyReviewProgression;
use App\GaelO\UseCases\GetStudyReviewProgression\GetStudyReviewProgressionRequest;
use App\GaelO\UseCases\GetStudyReviewProgression\GetStudyReviewProgressionResponse;
use App\GaelO\UseCases\GetReviewsFromVisitType\GetReviewsFromVisitType;
use App\GaelO\UseCases\GetReviewsFromVisitType\GetReviewsFromVisitTypeRequest;
use App\GaelO\UseCases\GetReviewsFromVisitType\GetReviewsFromVisitTypeResponse;
use App\GaelO\UseCases\GetReviewsMetadataFromVisitType\GetReviewsMetadataFromVisitType;
use App\GaelO\UseCases\GetReviewsMetadataFromVisitType\GetReviewsMetadataFromVisitTypeRequest;
use App\GaelO\UseCases\GetReviewsMetadataFromVisitType\GetReviewsMetadataFromVisitTypeResponse;
use App\GaelO\UseCases\GetStudies\GetStudies;
use App\GaelO\UseCases\GetStudies\GetStudiesRequest;
use App\GaelO\UseCases\GetStudies\GetStudiesResponse;
use App\GaelO\UseCases\GetStudiesWithDetails\GetStudiesWithDetails;
use App\GaelO\UseCases\GetStudiesWithDetails\GetStudiesWithDetailsRequest;
use App\GaelO\UseCases\GetStudiesWithDetails\GetStudiesWithDetailsResponse;
use App\GaelO\UseCases\GetStudy\GetStudy;
use App\GaelO\UseCases\GetStudy\GetStudyRequest;
use App\GaelO\UseCases\GetStudy\GetStudyResponse;
use App\GaelO\UseCases\GetStudyStatistics\GetStudyStatistics;
use App\GaelO\UseCases\GetStudyStatistics\GetStudyStatisticsRequest;
use App\GaelO\UseCases\GetStudyStatistics\GetStudyStatisticsResponse;
use App\GaelO\UseCases\GetStudyVisitTypes\GetStudyVisitTypes;
use App\GaelO\UseCases\GetStudyVisitTypes\GetStudyVisitTypesRequest;
use App\GaelO\UseCases\GetStudyVisitTypes\GetStudyVisitTypesResponse;
use App\GaelO\UseCases\GetVisitsTree\GetVisitsTree;
use App\GaelO\UseCases\GetVisitsTree\GetVisitsTreeRequest;
use App\GaelO\UseCases\GetVisitsTree\GetVisitsTreeResponse;
use App\GaelO\UseCases\ImportPatients\ImportPatients;
use App\GaelO\UseCases\ImportPatients\ImportPatientsRequest;
use App\GaelO\UseCases\ImportPatients\ImportPatientsResponse;
use App\GaelO\UseCases\ReactivateStudy\ReactivateStudy;
use App\GaelO\UseCases\ReactivateStudy\ReactivateStudyRequest;
use App\GaelO\UseCases\ReactivateStudy\ReactivateStudyResponse;
use App\GaelO\UseCases\RequestPatientCreation\RequestPatientCreation;
use App\GaelO\UseCases\RequestPatientCreation\RequestPatientCreationRequest;
use App\GaelO\UseCases\RequestPatientCreation\RequestPatientCreationResponse;
use App\GaelO\UseCases\SendReminder\SendReminder;
use App\GaelO\UseCases\SendReminder\SendReminderRequest;
use App\GaelO\UseCases\SendReminder\SendReminderResponse;
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
        $requestData = $request->all();

        Util::fillObject($requestData, $createStudyRequest);
        $createStudyRequest->currentUserId = $currentUser['id'];

        $createStudy->execute($createStudyRequest, $createStudyResponse);

        return $this->getJsonResponse($createStudyResponse->body, $createStudyResponse->status, $createStudyResponse->statusText);
    }

    public function getStudies(Request $request, GetStudies $getStudies, GetStudiesRequest $getStudiesRequest, GetStudiesResponse $getStudiesResponse, GetStudiesWithDetails $getStudiesWithDetails, GetStudiesWithDetailsRequest $getStudiesWithDetailsRequest, GetStudiesWithDetailsResponse $getStudiesWithDetailsResponse)
    {
        $currentUser = Auth::user();
        $queryParam = $request->query();
        if (array_key_exists('expand', $queryParam)) {
            $getStudiesWithDetailsRequest->currentUserId = $currentUser['id'];
            $getStudiesWithDetails->execute($getStudiesWithDetailsRequest, $getStudiesWithDetailsResponse);
            return $this->getJsonResponse($getStudiesWithDetailsResponse->body, $getStudiesWithDetailsResponse->status, $getStudiesWithDetailsResponse->statusText);
        } else {
            $getStudiesRequest->currentUserId = $currentUser['id'];
            $getStudiesRequest->withTrashed = key_exists('withTrashed', $queryParam);
            $getStudies->execute($getStudiesRequest, $getStudiesResponse);
            return $this->getJsonResponse($getStudiesResponse->body, $getStudiesResponse->status, $getStudiesResponse->statusText);
        }
    }

    public function getStudyVisitTypes(GetStudyVisitTypes $getStudyVisitTypes, GetStudyVisitTypesRequest $getStudyVisitTypesRequest, GetStudyVisitTypesResponse $getStudyVisitTypesResponse, string $studyName)
    {
        $currentUser = Auth::user();
        $getStudyVisitTypesRequest->currentUserId = $currentUser['id'];
        $getStudyVisitTypesRequest->studyName = $studyName;
        $getStudyVisitTypes->execute($getStudyVisitTypesRequest, $getStudyVisitTypesResponse);
        return $this->getJsonResponse($getStudyVisitTypesResponse->body, $getStudyVisitTypesResponse->status, $getStudyVisitTypesResponse->statusText);
    }

    public function deleteStudy(Request $request, DeleteStudy $deleteStudy,  DeleteStudyRequest $deleteStudyRequest, DeleteStudyResponse $deleteStudyResponse, String $studyName)
    {
        $currentUser = Auth::user();
        $requestData = $request->all();

        Util::fillObject($requestData, $deleteStudyRequest);
        $deleteStudyRequest->currentUserId = $currentUser['id'];
        $deleteStudyRequest->studyName = $studyName;

        $deleteStudy->execute($deleteStudyRequest, $deleteStudyResponse);

        return $this->getJsonResponse($deleteStudyResponse->body, $deleteStudyResponse->status, $deleteStudyResponse->statusText);
    }

    public function reactivateStudy(Request $request, ReactivateStudy $reactivateStudy, ReactivateStudyRequest $reactivateStudyRequest, ReactivateStudyResponse $reactivateStudyResponse, string $studyName)
    {
        $currentUser = Auth::user();
        $requestData = $request->all();

        Util::fillObject($requestData, $reactivateStudyRequest);
        $reactivateStudyRequest->currentUserId = $currentUser['id'];
        $reactivateStudyRequest->studyName = $studyName;

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

    public function getStudyReviewProgression(string $studyName, GetStudyReviewProgression $getStudyReviewProgression, GetStudyReviewProgressionRequest $getStudyReviewProgressionRequest, GetStudyReviewProgressionResponse $getStudyReviewProgressionResponse)
    {
        $currentUser = Auth::user();

        $getStudyReviewProgressionRequest->studyName = $studyName;
        $getStudyReviewProgressionRequest->currentUserId = $currentUser['id'];

        $getStudyReviewProgression->execute($getStudyReviewProgressionRequest, $getStudyReviewProgressionResponse);

        return $this->getJsonResponse($getStudyReviewProgressionResponse->body, $getStudyReviewProgressionResponse->status, $getStudyReviewProgressionResponse->statusText);
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


    public function getReviewsFromVisitType(Request $request, GetReviewsFromVisitType $getReviewsFromVisitType, GetReviewsFromVisitTypeRequest $getReviewsFromVisitTypeRequest, GetReviewsFromVisitTypeResponse $getReviewsFromVisitTypeResponse, string $studyName)
    {
        $currentUser = Auth::user();
        $queryParam = $request->query();
        $getReviewsFromVisitTypeRequest->studyName = $studyName;
        $getReviewsFromVisitTypeRequest->currentUserId = $currentUser['id'];
        $getReviewsFromVisitTypeRequest->visitTypeId = $queryParam['visitType'];

        $getReviewsFromVisitType->execute($getReviewsFromVisitTypeRequest, $getReviewsFromVisitTypeResponse);

        return $this->getJsonResponse($getReviewsFromVisitTypeResponse->body, $getReviewsFromVisitTypeResponse->status, $getReviewsFromVisitTypeResponse->statusText);
    }

    public function getReviewsMetadataFromVisitType(Request $request, GetReviewsMetadataFromVisitType $getReviewsMetadataFromVisitType, GetReviewsMetadataFromVisitTypeRequest $getReviewsMetadataFromVisitTypeRequest, GetReviewsMetadataFromVisitTypeResponse $getReviewsMetadataFromVisitTypeResponse, string $studyName)
    {

        $currentUser = Auth::user();
        $queryParam = $request->query();
        $getReviewsMetadataFromVisitTypeRequest->studyName = $studyName;
        $getReviewsMetadataFromVisitTypeRequest->currentUserId = $currentUser['id'];
        $getReviewsMetadataFromVisitTypeRequest->visitTypeId = $queryParam['visitType'];

        $getReviewsMetadataFromVisitType->execute($getReviewsMetadataFromVisitTypeRequest, $getReviewsMetadataFromVisitTypeResponse);

        return $this->getJsonResponse($getReviewsMetadataFromVisitTypeResponse->body, $getReviewsMetadataFromVisitTypeResponse->status, $getReviewsMetadataFromVisitTypeResponse->statusText);
    }

    public function getInvestigatorFormsFromVisitType(Request $request, GetInvestigatorFormsFromVisitType $getInvestigatorFormsFromVisitType, GetInvestigatorFormsFromVisitTypeRequest $getInvestigatorFormsFromVisitTypeRequest, GetInvestigatorFormsFromVisitTypeResponse $getInvestigatorFormsFromVisitTypeResponse, string $studyName)
    {
        $currentUser = Auth::user();
        $queryParam = $request->query();
        $getInvestigatorFormsFromVisitTypeRequest->studyName = $studyName;
        $getInvestigatorFormsFromVisitTypeRequest->currentUserId = $currentUser['id'];
        $getInvestigatorFormsFromVisitTypeRequest->visitTypeId = $queryParam['visitType'];

        $getInvestigatorFormsFromVisitType->execute($getInvestigatorFormsFromVisitTypeRequest, $getInvestigatorFormsFromVisitTypeResponse);

        return $this->getJsonResponse($getInvestigatorFormsFromVisitTypeResponse->body, $getInvestigatorFormsFromVisitTypeResponse->status, $getInvestigatorFormsFromVisitTypeResponse->statusText);
    }

    public function getInvestigatorFormsMetadataFromVisitType(Request $request, GetInvestigatorFormsMetadataFromVisitType $getInvestigatorFormsMetadataFromVisitType, GetInvestigatorFormsMetadataFromVisitTypeRequest $getInvestigatorFormsMetadataFromVisitTypeRequest, GetInvestigatorFormsMetadataFromVisitTypeResponse $getInvestigatorFormsMetadataFromVisitTypeResponse, string $studyName)
    {
        $currentUser = Auth::user();
        $queryParam = $request->query();
        $getInvestigatorFormsMetadataFromVisitTypeRequest->studyName = $studyName;
        $getInvestigatorFormsMetadataFromVisitTypeRequest->currentUserId = $currentUser['id'];
        $getInvestigatorFormsMetadataFromVisitTypeRequest->visitTypeId = $queryParam['visitType'];

        $getInvestigatorFormsMetadataFromVisitType->execute($getInvestigatorFormsMetadataFromVisitTypeRequest, $getInvestigatorFormsMetadataFromVisitTypeResponse);

        return $this->getJsonResponse($getInvestigatorFormsMetadataFromVisitTypeResponse->body, $getInvestigatorFormsMetadataFromVisitTypeResponse->status, $getInvestigatorFormsMetadataFromVisitTypeResponse->statusText);
    }

    public function getDicomStudiesFromStudy(Request $request, GetDicomsStudiesFromStudy $getDicomsStudiesFromStudy, GetDicomsStudiesFromStudyRequest $getDicomsStudiesFromStudyRequest, GetDicomsStudiesFromStudyResponse $getDicomsStudiesFromStudyResponse, string $studyName)
    {

        $currentUser = Auth::user();
        $queryParam = $request->query();
        $getDicomsStudiesFromStudyRequest->currentUserId = $currentUser['id'];
        $getDicomsStudiesFromStudyRequest->studyName = $studyName;
        $getDicomsStudiesFromStudyRequest->withTrashedStudies = key_exists('withTrashedStudies', $queryParam);
        $getDicomsStudiesFromStudyRequest->withTrashedSeries = key_exists('withTrashedSeries', $queryParam);

        $getDicomsStudiesFromStudy->execute($getDicomsStudiesFromStudyRequest, $getDicomsStudiesFromStudyResponse);

        return $this->getJsonResponse($getDicomsStudiesFromStudyResponse->body, $getDicomsStudiesFromStudyResponse->status, $getDicomsStudiesFromStudyResponse->statusText);
    }

    public function sendReminder(Request $request, SendReminder $sendReminder, SendReminderRequest $sendReminderRequest, SendReminderResponse $sendReminderResponse, string $studyName)
    {
        $currentUser = Auth::user();
        $requestData = $request->all();

        Util::fillObject($requestData, $sendReminderRequest);
        $sendReminderRequest->currentUserId = $currentUser['id'];
        $sendReminderRequest->studyName = $studyName;

        $sendReminder->execute($sendReminderRequest, $sendReminderResponse);
        return $this->getJsonResponse($sendReminderResponse->body, $sendReminderResponse->status, $sendReminderResponse->statusText);
    }

    public function requestPatientCreation(Request $request, RequestPatientCreation $requestPatientCreation, RequestPatientCreationRequest $requestPatientCreationRequest, RequestPatientCreationResponse $requestPatientCreationResponse, string $studyName)
    {
        $currentUser = Auth::user();
        $requestData = $request->all();
        $queryParam = $request->query();

        Util::fillObject($requestData, $requestPatientCreationRequest);
        $requestPatientCreationRequest->studyName = $studyName;
        $requestPatientCreationRequest->role = $queryParam['role'];
        $requestPatientCreationRequest->currentUserId = $currentUser['id'];

        $requestPatientCreation->execute($requestPatientCreationRequest, $requestPatientCreationResponse);

        return $this->getJsonResponse($requestPatientCreationResponse->body, $requestPatientCreationResponse->status, $requestPatientCreationResponse->statusText);
    }

    public function sendMail(Request $request, SendMail $sendMail, SendMailRequest $sendMailRequest, SendMailResponse $sendMailResponse)
    {
        $currentUser = Auth::user();
        $requestData = $request->all();
        $queryParam = $request->query();

        Util::fillObject($requestData, $sendMailRequest);
        $sendMailRequest->currentUserId = $currentUser['id'];
        $sendMailRequest->studyName = key_exists('studyName', $queryParam) ?  $queryParam['studyName'] : null;

        $sendMail->execute($sendMailRequest, $sendMailResponse);
        return $this->getJsonResponse($sendMailResponse->body, $sendMailResponse->status, $sendMailResponse->statusText);
    }

    public function getStudyStatistics(GetStudyStatistics $getStudyStatistics, GetStudyStatisticsRequest $getStudyStatisticsRequest, GetStudyStatisticsResponse $getStudyStatisticsResponse, string $studyName)
    {

        $currentUser = Auth::user();

        $getStudyStatisticsRequest->currentUserId = $currentUser['id'];
        $getStudyStatisticsRequest->studyName = $studyName;
        $getStudyStatistics->execute($getStudyStatisticsRequest, $getStudyStatisticsResponse);
        return $this->getJsonResponse($getStudyStatisticsResponse->body, $getStudyStatisticsResponse->status, $getStudyStatisticsResponse->statusText);
    }

    public function getStudy(GetStudy $getStudy, GetStudyRequest $getStudyRequest, GetStudyResponse $getStudyResponse, string $studyName)
    {
        $currentUser = Auth::user();

        $getStudyRequest->currentUserId = $currentUser['id'];
        $getStudyRequest->studyName = $studyName;
        $getStudy->execute($getStudyRequest, $getStudyResponse);
        return $this->getJsonResponse($getStudyResponse->body, $getStudyResponse->status, $getStudyResponse->statusText);
    }

    public function getCreatablePatients(Request $request, GetCreatablePatients $getCreatablePatients, GetCreatablePatientsRequest $getCreatablePatientsRequest, GetCreatablePatientsResponse $getCreatablePatientsResponse, string $studyName)
    {
        $currentUser = Auth::user();
        $queryParam = $request->query();

        $getCreatablePatientsRequest->currentUserId = $currentUser['id'];
        $getCreatablePatientsRequest->studyName = $studyName;
        $getCreatablePatientsRequest->role = $queryParam['role'];
        $getCreatablePatients->execute($getCreatablePatientsRequest, $getCreatablePatientsResponse);
        return $this->getJsonResponse($getCreatablePatientsResponse->body, $getCreatablePatientsResponse->status, $getCreatablePatientsResponse->statusText);
    }
}
