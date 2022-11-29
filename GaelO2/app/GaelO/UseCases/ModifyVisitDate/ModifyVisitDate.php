<?php

namespace App\GaelO\UseCases\ModifyVisitDate;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
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

            if($modifyVisitDateRequest->studyName !== $studyName){
                throw new GaelOForbiddenException("should be called from original study");
            }

            $this->checkAuthorization($currentUserId, $visitId, $studyName, $visitContext);

            //update visit Date in db
            $this->visitRepositoryInterface->updateVisitDate($visitId, $newVisitDate);

            $actionsDetails = [
                'patient_id' => $visitContext['patient_id'],
                'visit_group_name' => $visitContext['visit_type']['visit_group']['name'],
                'modality' => $visitContext['visit_type']['visit_group']['modality'],
                'visit_type_name' => $visitContext['visit_type']['name'],
                'previous_date' => $visitContext['visit_date'],
                'new_date' => $newVisitDate
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
        } catch (AbstractGaelOException $e) {
            $modifyVisitDateResponse->body = $e->getErrorBody();
            $modifyVisitDateResponse->status = $e->statusCode;
            $modifyVisitDateResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $userId, int $visitId, string $studyName, array $visitContext)
    {

        $this->authorizationVisitService->setUserId($userId);
        $this->authorizationVisitService->setVisitId($visitId);
        $this->authorizationVisitService->setStudyName($studyName);
        $this->authorizationVisitService->setVisitContext($visitContext);

        if (!$this->authorizationVisitService->isVisitAllowed(Constants::ROLE_SUPERVISOR)) {
            throw new GaelOForbiddenException();
        }
    }
}
