<?php

namespace App\GaelO\UseCases\RequestUnlock;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Repositories\TrackerRepository;
use App\GaelO\Services\AuthorizationVisitService;
use App\GaelO\Services\MailServices;
use Exception;

class RequestUnlock
{

    private AuthorizationVisitService $authorizationVisitService;
    private UserRepositoryInterface $userRepositoryInterface;
    private MailServices $mailServices;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private TrackerRepository $trackerRepository;

    public function __construct(UserRepositoryInterface $userRepositoryInterface, VisitRepositoryInterface $visitRepositoryInterface, AuthorizationVisitService $authorizationVisitService, MailServices $mailServices, TrackerRepository $trackerRepository)
    {
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->mailServices = $mailServices;
        $this->authorizationVisitService = $authorizationVisitService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->trackerRepository = $trackerRepository;
    }

    public function execute(RequestUnlockRequest $requestUnlockRequest, RequestUnlockResponse $requestUnlockResponse)
    {

        try {

            $this->checkAuthorization(
                $requestUnlockRequest->currentUserId,
                $requestUnlockRequest->role,
                $requestUnlockRequest->visitId
            );

            $userEntity = $this->userRepositoryInterface->find($requestUnlockRequest->currentUserId);

            $visitContext = $this->visitRepositoryInterface->getVisitContext($requestUnlockRequest->visitId);

            $patientCode = $visitContext['patient']['code'];
            $visitType = $visitContext['visit_type']['name'];

            if (empty($requestUnlockRequest->message)) {
                throw new GaelOBadRequestException('Unlock message should not be empty');
            }

            $this->mailServices->sendUnlockMessage(
                $requestUnlockRequest->visitId,
                $requestUnlockRequest->currentUserId,
                $requestUnlockRequest->role,
                $userEntity['firstname'],
                $userEntity['lastname'],
                $requestUnlockRequest->studyName,
                $patientCode,
                $requestUnlockRequest->message,
                $visitType
            );

            $formType = null;

            if($requestUnlockRequest->role === Constants::ROLE_INVESTIGATOR){
                $formType = 'Investigator';
            }else if ($requestUnlockRequest->role === Constants::ROLE_REVIEWER){
                $formType = 'Supervisor';
            }

            $details = [
                'form_type' => $formType,
                'message' => $requestUnlockRequest->message
            ];

            $this->trackerRepository->writeAction(
                $requestUnlockRequest->currentUserId,
                $requestUnlockRequest->role,
                $requestUnlockRequest->studyName,
                $requestUnlockRequest->visitId,
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

    private function checkAuthorization(int $userId, string $role, int $visitId)
    {

        $this->authorizationVisitService->setCurrentUserAndRole($userId, $role);
        $this->authorizationVisitService->setVisitId($visitId);
        if (!$this->authorizationVisitService->isVisitAllowed()) {
            throw new GaelOForbiddenException();
        }
    }
}
