<?php

namespace App\GaelO\UseCases\RequestUnlock;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Repositories\TrackerRepository;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use App\GaelO\Services\MailServices;
use Exception;

class RequestUnlock
{
    private AuthorizationVisitService $authorizationVisitService;
    private MailServices $mailServices;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private TrackerRepository $trackerRepository;

    public function __construct(VisitRepositoryInterface $visitRepositoryInterface, AuthorizationVisitService $authorizationVisitService, MailServices $mailServices, TrackerRepository $trackerRepository)
    {
        $this->mailServices = $mailServices;
        $this->authorizationVisitService = $authorizationVisitService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->trackerRepository = $trackerRepository;
    }

    public function execute(RequestUnlockRequest $requestUnlockRequest, RequestUnlockResponse $requestUnlockResponse)
    {

        try {

            $currentUserId = $requestUnlockRequest->currentUserId;
            $role = $requestUnlockRequest->role;
            $visitId = $requestUnlockRequest->visitId;
            $studyName = $requestUnlockRequest->studyName;
            $message = $requestUnlockRequest->message;

            $this->checkAuthorization(
                $currentUserId,
                $role,
                $visitId,
                $studyName
            );

            $visitContext = $this->visitRepositoryInterface->getVisitContext($visitId);
            $patientId = $visitContext['patient']['id'];
            $patientCode = $visitContext['patient']['code'];
            $visitType = $visitContext['visit_type']['name'];

            if (empty($message)) {
                throw new GaelOBadRequestException('Unlock message should not be empty');
            }

            $this->mailServices->sendUnlockMessage(
                $visitId,
                $currentUserId,
                $role,
                $studyName,
                $patientId,
                $patientCode,
                $message,
                $visitType
            );

            $formType = null;

            if ($role === Constants::ROLE_INVESTIGATOR) {
                $formType = 'Investigator';
            } else if ($role === Constants::ROLE_REVIEWER) {
                $formType = 'Reviewer';
            }

            $details = [
                'form_type' => $formType,
                'message' => $message
            ];

            $this->trackerRepository->writeAction(
                $currentUserId,
                $role,
                $studyName,
                $visitId,
                Constants::TRACKER_ASK_UNLOCK,
                $details
            );

            $requestUnlockResponse->status = 200;
            $requestUnlockResponse->statusText = 'OK';
        } catch (GaelOException $e) {
            $requestUnlockResponse->body = $e->getErrorBody();
            $requestUnlockResponse->status = $e->statusCode;
            $requestUnlockResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $userId, string $role, int $visitId, string $studyName)
    {
        $this->authorizationVisitService->setUserId($userId);
        $this->authorizationVisitService->setVisitId($visitId);
        $this->authorizationVisitService->setStudyName($studyName);
        if (!$this->authorizationVisitService->isVisitAllowed($role)) {
            throw new GaelOForbiddenException();
        }
    }
}
