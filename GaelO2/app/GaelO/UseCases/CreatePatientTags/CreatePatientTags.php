<?php

namespace App\GaelO\UseCases\CreatePatientTags;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class CreatePatientTags
{
    private AuthorizationUserService $authorizationUserService;
    private PatientRepositoryInterface $patientRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(
        AuthorizationUserService $authorizationUserService,
        PatientRepositoryInterface $patientRepositoryInterface,
        TrackerRepositoryInterface $trackerRepositoryInterface
    ) {
        $this->authorizationUserService = $authorizationUserService;
        $this->patientRepositoryInterface = $patientRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function execute(CreatePatientTagsRequest $createPatientTagsRequest, CreatePatientTagsResponse $createPatientTagsResponse)
    {

        try {
            $currentUserId = $createPatientTagsRequest->currentUserId;
            $patientId = $createPatientTagsRequest->patientId;
            $tag = $createPatientTagsRequest->tag;
            $studyName = $createPatientTagsRequest->studyName;


            $patientEntity = $this->patientRepositoryInterface->find($patientId);

            if ($patientEntity['study_name'] !== $studyName) {
                throw new GaelOForbiddenException('Should be called from the original study');
            };

            $this->checkAuthorization($currentUserId, $studyName);

            if (in_array($tag, $patientEntity['metadata']['tags'])) {
                throw new GaelOBadRequestException('Existing Tag in patient');
            }

            //Add tag in tags of this patient
            $patientEntity['metadata']['tags'][] = $tag;

            $this->patientRepositoryInterface->updatePatient(
                $patientId,
                $patientEntity['lastname'],
                $patientEntity['firstname'],
                $patientEntity['gender'],
                $patientEntity['birth_day'],
                $patientEntity['birth_month'],
                $patientEntity['birth_year'],
                $patientEntity['study_name'],
                $patientEntity['registration_date'],
                $patientEntity['investigator_name'],
                $patientEntity['center_code'],
                $patientEntity['inclusion_status'],
                $patientEntity['withdraw_reason'],
                $patientEntity['withdraw_date'],
                $patientEntity['metadata']
            );

            $actionDetails = [
                'id' => $patientEntity['id'],
                'code' => $patientEntity['code'],
                'add_tag' => $tag
            ];

            $this->trackerRepositoryInterface->writeAction($currentUserId, Constants::ROLE_SUPERVISOR, $patientEntity['study_name'], null, Constants::TRACKER_EDIT_PATIENT, (array) $actionDetails);

            $createPatientTagsResponse->status = 201;
            $createPatientTagsResponse->statusText =  'Created';
        } catch (AbstractGaelOException $e) {
            $createPatientTagsResponse->body = $e->getErrorBody();
            $createPatientTagsResponse->status = $e->statusCode;
            $createPatientTagsResponse->statusText =  $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function checkAuthorization(int $userId, string $studyName)
    {
        $this->authorizationUserService->setUserId($userId);
        if (!$this->authorizationUserService->isRoleAllowed(Constants::ROLE_SUPERVISOR, $studyName)) {
            throw new GaelOForbiddenException();
        }
    }
}
