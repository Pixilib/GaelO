<?php

namespace App\GaelO\UseCases\ModifyVisitDate;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationVisitService;
use Exception;

class ModifyVisitDate {

    private AuthorizationVisitService $authorizationVisitService;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(AuthorizationVisitService $authorizationVisitService, VisitRepositoryInterface $visitRepositoryInterface, TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function execute(ModifyVisitDateRequest $modifyVisitDateRequest, ModifyVisitDateResponse $modifyVisitDateResponse){

        try{

            $visitId = $modifyVisitDateRequest->visitId;
            $currentUserId = $modifyVisitDateRequest->currentUserId;
            $newVisitDate = $modifyVisitDateRequest->visitDate;

            $this->checkAuthorization($currentUserId, $visitId);

            $visitContext = $this->visitRepositoryInterface->getVisitContext($visitId);
            $studyName = $visitContext['visit_type']['visit_group']['study_name'];

            //update visit Date in db
            $this->visitRepositoryInterface->updateVisitDate($visitId, $newVisitDate);

            $actionsDetails = [
                'patientId'=>$visitContext['patient_id'],
                'modality' => $visitContext['visit_type']['visit_group']['modality'],
                'visitType' => $visitContext['visit_type']['name'],
                'previousDate' =>$visitContext['visit_date'],
                'newDate' => $newVisitDate
            ];

            //Write in Tracker
            $this->trackerRepositoryInterface->writeAction(
                $modifyVisitDateRequest->currentUserId,
                Constants::ROLE_SUPERVISOR,
                $studyName,
                $modifyVisitDateRequest->visitId,
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

    private function checkAuthorization(int $userId, int $visitId){

        $this->authorizationVisitService->setCurrentUserAndRole($userId, Constants::ROLE_SUPERVISOR);
        $this->authorizationVisitService->setVisitId($visitId);
        if(!$this->authorizationVisitService->isVisitAllowed()){
            throw new GaelOForbiddenException();
        }

    }

}
