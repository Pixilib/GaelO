<?php

namespace App\GaelO\UseCases\DeletePatientTags;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Exceptions\GaelONotFoundException;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class DeletePatientTags
{
    private AuthorizationUserService $authorizationUserService;
    private PatientRepositoryInterface $patientRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(AuthorizationUserService $authorizationUserService, PatientRepositoryInterface $patientRepositoryInterface, TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->authorizationUserService = $authorizationUserService;
        $this->patientRepositoryInterface = $patientRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function execute(DeletePatientTagsRequest $deletePatientTagsRequest, DeletePatientTagsResponse $deletePatientTagsResponse)
    {

        try {
            $currentUserId = $deletePatientTagsRequest->currentUserId;
            $patientId = $deletePatientTagsRequest->patientId;
            $tag = $deletePatientTagsRequest->tag;
            $studyName = $deletePatientTagsRequest->studyName;


            $patientEntity = $this->patientRepositoryInterface->find($patientId);

            if ($patientEntity['study_name'] !== $studyName) {
                throw new GaelOForbiddenException('Should be called from the original study');
            };

            $this->checkAuthorization($currentUserId, $studyName);

            //Add tag in tags of this patient
            if (($key = array_search($tag, $patientEntity['metadata']['tags'])) !== false) {
                unset($patientEntity['metadata']['tags'][$key]);
            }else{
                throw new GaelONotFoundException('Not Existing Tag in patient');
            }

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
                'delete_tag' => $tag
            ];

            $this->trackerRepositoryInterface->writeAction($currentUserId, Constants::ROLE_SUPERVISOR, $patientEntity['study_name'], null, Constants::TRACKER_EDIT_PATIENT, (array) $actionDetails);

            $deletePatientTagsResponse->status = 200;
            $deletePatientTagsResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $deletePatientTagsResponse->body = $e->getErrorBody();
            $deletePatientTagsResponse->status = $e->statusCode;
            $deletePatientTagsResponse->statusText =  $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }

    }

    private function checkAuthorization(int $userId, string $studyName)
    {
        $this->authorizationUserService->setUserId($userId);
        if (!$this->authorizationUserService->isRoleAllowed(Constants::ROLE_SUPERVISOR, $studyName)) {
            throw new GaelOForbiddenException();
        }
    }
}