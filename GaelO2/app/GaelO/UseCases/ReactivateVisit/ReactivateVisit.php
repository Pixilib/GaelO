<?php

namespace App\GaelO\UseCases\ReactivateVisit;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use Exception;

class ReactivateVisit
{

    private AuthorizationStudyService $authorizationStudyService;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(
        AuthorizationStudyService $authorizationStudyService,
        VisitRepositoryInterface $visitRepositoryInterface,
        TrackerRepositoryInterface $trackerRepositoryInterface
    ) {
        $this->authorizationStudyService = $authorizationStudyService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function execute(ReactivateVisitRequest $reactivateVisitRequest, ReactivateVisitResponse $reactivateVisitResponse)
    {

        try {

            $visitContext = $this->visitRepositoryInterface->getVisitContext($reactivateVisitRequest->visitId, true);

            $currentUserId = $reactivateVisitRequest->currentUserId;
            $studyName = $visitContext['patient']['study_name'];
            $visitType = $visitContext['visit_type']['name'];
            $visitGroupName = $visitContext['visit_type']['visit_group']['name'];
            $modality = $visitContext['visit_type']['visit_group']['modality'];
            $patientId = $visitContext['patient_id'];
            $visitId = $visitContext['id'];

            $this->checkAuthorization($currentUserId, $studyName);

            $isExisitingVisit = $this->visitRepositoryInterface->isExistingVisit($patientId, $visitContext['visit_type']['id']);

            if ($visitContext['deleted_at'] == null) {
                throw new GaelOConflictException("Visit Not Deleted, can't reactivate it");
            }

            if ($isExisitingVisit) {
                throw new GaelOConflictException("Already existing visit for this Patient / Visit Type, delete it first");
            }

            $this->visitRepositoryInterface->reactivateVisit($visitId);

            $actionDetails = [
                'visit_type_name' => $visitType,
                'visit_group_name' => $visitGroupName,
                'visit_group_modality' => $modality,
                'patient_id' => $patientId
            ];

            $this->trackerRepositoryInterface->writeAction(
                $currentUserId,
                Constants::ROLE_SUPERVISOR,
                $studyName,
                $visitId,
                Constants::TRACKER_REACTIVATE_VISIT,
                $actionDetails
            );

            $reactivateVisitResponse->status = 200;
            $reactivateVisitResponse->statusText = 'OK';
        } catch (GaelOException $e) {
            $reactivateVisitResponse->status = $e->statusCode;
            $reactivateVisitResponse->statusText = $e->statusText;
            $reactivateVisitResponse->body = $e->getErrorBody();
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $currentUserId, string $studyName)
    {
        $this->authorizationStudyService->setUserId($currentUserId);
        $this->authorizationStudyService->setStudyName($studyName);
        if (!$this->authorizationStudyService->isAllowedStudy(Constants::ROLE_SUPERVISOR)) {
            throw new GaelOForbiddenException();
        }
    }
}
