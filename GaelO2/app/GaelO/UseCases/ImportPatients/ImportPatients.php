<?php

namespace App\GaelO\UseCases\ImportPatients;

use App\GaelO\Constants\Constants;

use App\GaelO\UseCases\ImportPatients\ImportPatientsRequest;
use App\GaelO\UseCases\ImportPatients\ImportPatientsResponse;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\ImportPatientService;
use App\GaelO\Util;
use Exception;

class ImportPatients
{
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private StudyRepositoryInterface $studyRepositoryInterface;
    private MailServices $mailServices;
    private ImportPatientService $importPatient;
    private AuthorizationStudyService $authorizationStudyService;

    public function __construct(
        TrackerRepositoryInterface $trackerRepositoryInterface,
        StudyRepositoryInterface $studyRepositoryInterface,
        MailServices $mailServices,
        ImportPatientService $importPatient,
        AuthorizationStudyService $authorizationStudyService
    ) {
        $this->importPatient = $importPatient;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->mailServices = $mailServices;
        $this->authorizationStudyService = $authorizationStudyService;
    }

    public function execute(ImportPatientsRequest $importPatientsRequest, ImportPatientsResponse $importPatientsResponse): void
    {
        try {
            $studyName = $importPatientsRequest->studyName;
            $currentUserId = $importPatientsRequest->currentUserId;
            $patients = $importPatientsRequest->patients;
            $role = $importPatientsRequest->role;

            $this->checkAuthorization($currentUserId, $studyName, $role);

            $arrayPatients = [];
            foreach ($patients as $patient) {

                foreach ($patient as $key => $value) {
                    $patient[Util::camelCaseToSnakeCase($key)] = $value;
                }
                $patient['studyName'] = $studyName;
                $arrayPatients[] = $patient;
            }
            $studyEntity = $this->studyRepositoryInterface->find($studyName);

            $this->importPatient->setStudyEntity($studyEntity);
            $this->importPatient->setPatientEntities($arrayPatients);

            //Import Patient with service
            $this->importPatient->import();

            //Save action in Tracker
            $actionDetails['Success'] = $this->importPatient->successList;
            $actionDetails['Fail'] = $this->importPatient->failList;

            $importPatientsResponse->body = ['success' => $this->importPatient->successList, 'fail' => $this->importPatient->failList];
            $importPatientsResponse->status = 200;
            $importPatientsResponse->statusText = 'OK';

            $this->trackerRepositoryInterface->writeAction($currentUserId, Constants::ROLE_SUPERVISOR, $studyName, null, Constants::TRACKER_IMPORT_PATIENT, $actionDetails);

            $this->mailServices->sendImportPatientMessage($studyName, $studyEntity->contactEmail, $this->importPatient->successList, $this->importPatient->failList);
        } catch (AbstractGaelOException $e) {
            $importPatientsResponse->body = $e->getErrorBody();
            $importPatientsResponse->status = $e->statusCode;
            $importPatientsResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $userId, string $studyName, string $role)
    {
        $this->authorizationStudyService->setUserId($userId);
        $this->authorizationStudyService->setStudyName($studyName);
        if ($this->authorizationStudyService->getStudyEntity()->isAncillaryStudy()) {
            throw new GaelOForbiddenException("Forbidden for ancillary studies");
        }
        if (!in_array($role, [Constants::ROLE_INVESTIGATOR, Constants::ROLE_SUPERVISOR])) {
            throw new GaelOForbiddenException("Role forbidden");
        }
        if ($role === Constants::ROLE_INVESTIGATOR && !$this->authorizationStudyService->getStudyEntity()->creatablePatientsInvestigator) {
            throw new GaelOForbiddenException("Patient creation disallowed for investigator");
        }
        if (!$this->authorizationStudyService->isAllowedStudy($role)) {
            throw new GaelOForbiddenException();
        }
    }
}
