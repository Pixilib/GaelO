<?php

namespace App\GaelO\UseCases\ModifyVisitDate;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use Exception;

class ModifyVisitDate
{

    private AuthorizationVisitService $authorizationVisitService;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(AuthorizationVisitService $authorizationVisitService, VisitRepositoryInterface $visitRepositoryInterface, TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function execute(ModifyVisitDateRequest $modifyVisitDateRequest, ModifyVisitDateResponse $modifyVisitDateResponse)
    {

        try {

            $visitId = $modifyVisitDateRequest->visitId;
            $currentUserId = $modifyVisitDateRequest->currentUserId;
            $newVisitDate = $modifyVisitDateRequest->visitDate;

            $visitContext = $this->visitRepositoryInterface->getVisitContext($visitId);
            $studyName = $visitContext['patient']['study_name'];

            $this->checkAuthorization($currentUserId, $visitId, $studyName);

            //update visit Date in db
            $this->visitRepositoryInterface->updateVisitDate($visitId, $newVisitDate);

            $actionsDetails = [
                'patientId' => $visitContext['patient_id'],
                'modality' => $visitContext['visit_type']['visit_group']['modality'],
                'visitType' => $visitContext['visit_type']['name'],
                'previousDate' => $visitContext['visit_date'],
                'newDate' => $newVisitDate
            ];

            //Write in Tracker
            $this->trackerRepositoryInterface->writeAction(
                $currentUserId,
                Constants::ROLE_SUPERVISOR,
                $studyName,
                $visitId,
                Constants::TRACKER_UPDATE_VISIT_DATE,
                $actionsDetails

            );

            $modifyVisitDateResponse->status = 200;
            $modifyVisitDateResponse->statusText = 'OK';
        } catch (GaelOException $e) {
            $modifyVisitDateResponse->body = $e->getErrorBody();
            $modifyVisitDateResponse->status = $e->statusCode;
            $modifyVisitDateResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        };
    }

    private function checkAuthorization(int $userId, int $visitId, string $studyName)
    {

        $this->authorizationVisitService->setUserId($userId);
        $this->authorizationVisitService->setVisitId($visitId);
        $this->authorizationVisitService->setStudyName($studyName);

        if (!$this->authorizationVisitService->isVisitAllowed(Constants::ROLE_SUPERVISOR)) {
            throw new GaelOForbiddenException();
        }
    }
}
