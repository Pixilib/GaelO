<?php

namespace App\GaelO\UseCases\DeleteVisit;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use Exception;

class DeleteVisit
{

    private VisitRepositoryInterface $visitRepositoryInterface;
    private AuthorizationVisitService $authorizationVisitService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(
        VisitRepositoryInterface $visitRepositoryInterface,
        AuthorizationVisitService $authorizationVisitService,
        TrackerRepositoryInterface $trackerRepositoryInterface
    ) {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function execute(DeleteVisitRequest $deleteVisitRequest, DeleteVisitResponse $deleteVisitResponse)
    {

        try {

            if (empty($deleteVisitRequest->reason)) throw new GaelOBadRequestException('Reason must be specified');

            $visitContext  = $this->visitRepositoryInterface->getVisitContext($deleteVisitRequest->visitId);

            $studyName = $visitContext['patient']['study_name'];
            $visitTypeName = $visitContext['visit_type']['name'];
            $patientId = $visitContext['patient']['id'];
            $visitTypeName = $visitContext['visit_type']['name'];
            $qcStatus = $visitContext['state_quality_control'];
            $visitId = $visitContext['id'];
            $currentUserId = $deleteVisitRequest->currentUserId;
            $role = $deleteVisitRequest->role;
            $reason = $deleteVisitRequest->reason;


            $this->checkAuthorization(
                $currentUserId,
                $role,
                $visitId,
                $qcStatus,
                $studyName
            );

            $this->visitRepositoryInterface->delete($visitId);

            $actionDetails  = [
                'patient_id' => $patientId,
                'type_visit' => $visitTypeName,
                'reason' => $reason
            ];

            $this->trackerRepositoryInterface->writeAction(
                $currentUserId,
                $role,
                $studyName,
                $visitId,
                Constants::TRACKER_DELETE_VISIT,
                $actionDetails
            );

            $deleteVisitResponse->status = 200;
            $deleteVisitResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $deleteVisitResponse->body = $e->getErrorBody();
            $deleteVisitResponse->status = $e->statusCode;
            $deleteVisitResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }


    public function checkAuthorization(int $userId, string $role, int $visitId, string $qcStatus, string $studyName)
    {

        //This role only allowed for Investigator and Supervisor Roles
        if (!in_array($role, [Constants::ROLE_INVESTIGATOR, Constants::ROLE_SUPERVISOR])) {
            throw new GaelOForbiddenException();
        }

        //If Investigator, only possible to delete Visits with Non finished QC
        if ($role === Constants::ROLE_INVESTIGATOR && in_array($qcStatus, [Constants::QUALITY_CONTROL_REFUSED, Constants::QUALITY_CONTROL_ACCEPTED])) {
            throw new GaelOForbiddenException();
        }

        $this->authorizationVisitService->setUserId($userId);
        $this->authorizationVisitService->setVisitId($visitId);
        $this->authorizationVisitService->setStudyName($studyName);

        if (!$this->authorizationVisitService->isVisitAllowed($role)) {
            throw new GaelOForbiddenException();
        }
    }
}
