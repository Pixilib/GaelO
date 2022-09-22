<?php

namespace App\GaelO\UseCases\RequestPatientCreation;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use App\GaelO\Services\MailServices;

class RequestPatientCreation
{
    private MailServices $mailService;
    private AuthorizationStudyService $authorizationStudyService;

    public function __construct(
        MailServices $mailService,
        AuthorizationStudyService $authorizationStudyService,
        TrackerRepositoryInterface $trackerRepositoryInterface
    ) {
        $this->mailService = $mailService;
        $this->authorizationStudyService = $authorizationStudyService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function execute(RequestPatientCreationRequest $requestPatientCreationRequest, RequestPatientCreationResponse $requestPatientCreationResponse)
    {

        try {
            $currentUserId = $requestPatientCreationRequest->currentUserId;
            $studyName = $requestPatientCreationRequest->studyName;
            $role = $requestPatientCreationRequest->role;
            $patients = $requestPatientCreationRequest->patients;
            $content = $requestPatientCreationRequest->content;

            if (count($patients) === 0) throw new GaelOBadRequestException('Request missing patient list');
            $this->checkAuthorization($currentUserId, $studyName, $role);
            $this->mailService->sendPatientCreationRequest($currentUserId, $studyName, $content, $patients);

            $actionsDetails = [
                'patient_creation_request' => $patients
            ];

            $this->trackerRepositoryInterface->writeAction(
                $currentUserId,
                $role,
                $studyName,
                null,
                Constants::TRACKER_SEND_MESSAGE,
                $actionsDetails
            );

            $requestPatientCreationResponse->status = 200;
            $requestPatientCreationResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $requestPatientCreationResponse->body = $e->getErrorBody();
            $requestPatientCreationResponse->status = $e->statusCode;
            $requestPatientCreationResponse->statusText = $e->statusText;
        }
    }

    private function checkAuthorization(int $userId, string $study, string $role)
    {
        if ($role !== Constants::ROLE_INVESTIGATOR) throw new GaelOForbiddenException("Patient Creation request is only for investigator role");
        $this->authorizationStudyService->setUserId($userId);
        $this->authorizationStudyService->setStudyName($study);
        if (!$this->authorizationStudyService->isAllowedStudy($role)) {
            throw new GaelOForbiddenException();
        }
    }
}
