<?php

namespace App\GaelO\UseCases\CreateStudy;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;


class CreateStudy
{

    private StudyRepositoryInterface $studyRepositoryInterface;
    private AuthorizationUserService $authorizationUserService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(StudyRepositoryInterface $studyRepositoryInterface, AuthorizationUserService $authorizationUserService, TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->authorizationUserService = $authorizationUserService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function execute(CreateStudyRequest $createStudyRequest, CreateStudyResponse $createStudyResponse)
    {

        try {
            $this->checkAuthorization($createStudyRequest->currentUserId);

            $studyName = $createStudyRequest->name;
            $studyCode = $createStudyRequest->code;
            $patientCodeLength = $createStudyRequest->patientCodeLength;
            $controllerShowAll = $createStudyRequest->controllerShowAll;
            $monitorShowAll = $createStudyRequest->monitorShowAll;
            $documentationMandatory = $createStudyRequest->documentationMandatory;
            $contactEmail = $createStudyRequest->contactEmail;
            $ancillaryOf = $createStudyRequest->ancillaryOf;
            $creatablePatientsInvestigator = $createStudyRequest->creatablePatientsInvestigator;
            $investigatorOwnVisits = $createStudyRequest->investigatorOwnVisits;

            if (preg_match('/[^A-Z0-9]/', $studyName)) {
                throw new GaelOBadRequestException('Only uppercase alphanumerical name allowed, no space or special characters');
            }

            if ($this->studyRepositoryInterface->isExistingStudyName($studyName)) {
                throw new GaelOConflictException('Already Existing Study');
            }

            if ($this->studyRepositoryInterface->isExistingStudyCode($studyCode)) {
                throw new GaelOConflictException('Already used study code');
            }

            if (empty($patientCodeLength)) {
                throw new GaelOBadRequestException('Missing Patient Code Lenght');
            }

            if (empty($contactEmail)) {
                throw new GaelOBadRequestException('Missing Contact Email');
            }

            if (!isset($controllerShowAll)) {
                throw new GaelOBadRequestException('Missing Controller Show All');
            }

            if (!isset($monitorShowAll)) {
                throw new GaelOBadRequestException('Missing Monitor Show All');
            }

            if (!isset($creatablePatientsInvestigator)) {
                throw new GaelOBadRequestException('Missing Creatable Patient Investigator');
            }

            if (!isset($investigatorOwnVisits)) {
                throw new GaelOBadRequestException('Missing Investigator Own Visit');
            }

            $this->studyRepositoryInterface->addStudy($studyName, $studyCode, $patientCodeLength, $contactEmail, $controllerShowAll, $monitorShowAll, $documentationMandatory, $ancillaryOf, $creatablePatientsInvestigator, $investigatorOwnVisits);

            $currentUserId = $createStudyRequest->currentUserId;
            $actionDetails = [
                'study_name' => $studyName,
                'study_code' => $studyCode,
                'ancillary_of' => $ancillaryOf
            ];

            $this->trackerRepositoryInterface->writeAction($currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, null, null, Constants::TRACKER_CREATE_STUDY, $actionDetails);

            $createStudyResponse->status = 201;
            $createStudyResponse->statusText = 'Created';
        } catch (AbstractGaelOException $e) {
            $createStudyResponse->body = $e->getErrorBody();
            $createStudyResponse->status = $e->statusCode;
            $createStudyResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $currentUserId)
    {
        $this->authorizationUserService->setUserId($currentUserId);
        if (!$this->authorizationUserService->isAdmin($currentUserId)) {
            throw new GaelOForbiddenException();
        }
    }
}
