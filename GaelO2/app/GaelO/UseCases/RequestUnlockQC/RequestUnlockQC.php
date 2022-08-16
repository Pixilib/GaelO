<?php

namespace App\GaelO\UseCases\RequestUnlockQC;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Repositories\TrackerRepository;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use App\GaelO\Services\MailServices;

use Exception;

class RequestUnlockQC
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

    public function execute(RequestUnlockQCRequest $requestUnlockQCRequest, RequestUnlockQCResponse $requestUnlockQCResponse)
    {

        try {
            $visitId = $requestUnlockQCRequest->visitId;
            $currentUserId = $requestUnlockQCRequest->currentUserId;

            $visitContext = $this->visitRepositoryInterface->getVisitContext($visitId);

            //StudyName extracted from visit as it apply only for original study
            $studyName = $visitContext['patient']['study_name'];

            $this->checkAuthorization(
                $currentUserId,
                $visitId,
                $studyName
            );

            $patientId = $visitContext['patient']['id'];
            $patientCode = $visitContext['patient']['code'];
            $visitType = $visitContext['visit_type']['name'];

            if (empty($requestUnlockQCRequest->message)) {
                throw new GaelOBadRequestException('Unlock message should not be empty');
            }

            $message = $requestUnlockQCRequest->message;

            $this->mailServices->sendUnlockQCMessage(
                $visitId,
                $currentUserId,
                $studyName,
                $patientId,
                $patientCode,
                $message,
                $visitType
            );

            $details = [
                'form_type' => 'QC Reset',
                'message' => $message
            ];

            $this->trackerRepository->writeAction(
                $currentUserId,
                Constants::ROLE_CONTROLLER,
                $studyName,
                $visitId,
                Constants::TRACKER_ASK_UNLOCK,
                $details
            );

            $requestUnlockQCResponse->status = 200;
            $requestUnlockQCResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $requestUnlockQCResponse->body = $e->getErrorBody();
            $requestUnlockQCResponse->status = $e->statusCode;
            $requestUnlockQCResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $userId, int $visitId, string $studyName)
    {
        $this->authorizationVisitService->setUserId($userId);
        $this->authorizationVisitService->setVisitId($visitId);
        $this->authorizationVisitService->setStudyName($studyName);
        if (!$this->authorizationVisitService->isVisitAllowed(Constants::ROLE_CONTROLLER)) {
            throw new GaelOForbiddenException();
        }
    }
}
