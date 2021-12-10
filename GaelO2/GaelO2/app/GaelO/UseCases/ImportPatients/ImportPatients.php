<?php

namespace App\GaelO\UseCases\ImportPatients;

use App\GaelO\Constants\Constants;

use App\GaelO\UseCases\ImportPatients\ImportPatientsRequest;
use App\GaelO\UseCases\ImportPatients\ImportPatientsResponse;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\ImportPatientService;
use App\GaelO\Util;
use Exception;

class ImportPatients
{

    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private MailServices $mailService;
    private ImportPatientService $importPatient;
    private AuthorizationStudyService $authorizationStudyService;

    public function __construct(TrackerRepositoryInterface $trackerRepositoryInterface, MailServices $mailService, ImportPatientService $importPatient, AuthorizationStudyService $authorizationStudyService)
    {
        $this->importPatient = $importPatient;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->mailService = $mailService;
        $this->authorizationStudyService = $authorizationStudyService;
    }

    public function execute(ImportPatientsRequest $importPatientsRequest, ImportPatientsResponse $importPatientsResponse): void
    {
        try {

            $this->checkAuthorization($importPatientsRequest->currentUserId, $importPatientsRequest->studyName);
            $arrayPatients = [];
            foreach ($importPatientsRequest->patients as $patient) {

                foreach ($patient as $key => $value) {
                    $patient[Util::camelCaseToSnakeCase($key)] = $value;
                }
                $patient['studyName'] = $importPatientsRequest->studyName;
                $arrayPatients[] = $patient;
            }
            $importPatientsRequest->patients = $arrayPatients;
            $this->importPatient->setPatientEntities($importPatientsRequest->patients);
            $this->importPatient->setStudyName($importPatientsRequest->studyName);

            //Check form completion
            $this->importPatient->import();

            //Save action in Tracker
            $actionDetails['Success'] = $this->importPatient->successList;
            $actionDetails['Fail'] = $this->importPatient->failList;

            $importPatientsResponse->body = ['success' => $this->importPatient->successList, 'fail' => $this->importPatient->failList];
            $importPatientsResponse->status = 200;
            $importPatientsResponse->statusText = 'OK';

            $this->trackerRepositoryInterface->writeAction($importPatientsRequest->currentUserId, Constants::TRACKER_IMPORT_PATIENT, $importPatientsRequest->studyName, null, Constants::TRACKER_IMPORT_PATIENT, $actionDetails);

            $this->mailService->sendImportPatientMessage($importPatientsRequest->studyName, $this->importPatient->successList, $this->importPatient->failList);
        } catch (GaelOException $e) {

            $importPatientsResponse->body = $e->getErrorBody();
            $importPatientsResponse->status = $e->statusCode;
            $importPatientsResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $userId, string $studyName)
    {
        $this->authorizationStudyService->setUserId($userId);
        $this->authorizationStudyService->setStudyName($studyName);
        if ($this->authorizationStudyService->isAncillaryStudy() ) {
            throw new GaelOForbiddenException("Forbidden for ancillaries study");
        };
        if (!$this->authorizationStudyService->isAllowedStudy(Constants::ROLE_SUPERVISOR)) {
            throw new GaelOForbiddenException();
        };
    }
}
