<?php

namespace App\GaelO\UseCases\RequestUnlockQC;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
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

            $visitContext = $this->visitRepositoryInterface->getVisitContext($requestUnlockQCRequest->visitId);

            //StudyName extracted from visit as it apply only for original study
            $studyName = $visitContext['patient']['study_name'];

            $this->checkAuthorization(
                $requestUnlockQCRequest->currentUserId,
                $requestUnlockQCRequest->role,
                $requestUnlockQCRequest->visitId,
                $studyName
            );

            $patientId = $visitContext['patient']['id'];
            $visitType = $visitContext['visit_type']['name'];

            if (empty($requestUnlockQCRequest->message)) {
                throw new GaelOBadRequestException('Unlock message should not be empty');
            }

            //SK RESTE A FAIRE MODELE DE MAIL
            $this->mailServices->sendUnlockQCMessage(
                $requestUnlockQCRequest->visitId,
                $requestUnlockQCRequest->currentUserId,
                $requestUnlockQCRequest->role,
                $studyName,
                $patientId,
                $requestUnlockQCRequest->message,
                $visitType
            );

            $details = [
                'form_type' => 'QC Reset',
                'message' => $requestUnlockQCRequest->message
            ];

            $this->trackerRepository->writeAction(
                $requestUnlockQCRequest->currentUserId,
                $requestUnlockQCRequest->role,
                $studyName,
                $requestUnlockQCRequest->visitId,
                Constants::TRACKER_ASK_UNLOCK,
                $details
            );

            $requestUnlockQCResponse->status = 200;
            $requestUnlockQCResponse->statusText = 'OK';

        } catch (GaelOException $e) {

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
