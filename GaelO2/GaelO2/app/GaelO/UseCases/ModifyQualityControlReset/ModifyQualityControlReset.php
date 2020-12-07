<?php

namespace App\GaelO\UseCases\ModifyQualityControlReset;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationVisitService;
use App\GaelO\Services\TrackerService;
use App\GaelO\Services\VisitService;
use Exception;

class ModifyQualityControlReset{

    private AuthorizationVisitService $authorizationVisitService;
    private TrackerService $trackerService;
    private VisitService $visitService;

    public function __construct(AuthorizationVisitService $authorizationVisitService, VisitService $visitService, TrackerService $trackerService){

        $this->authorizationVisitService = $authorizationVisitService;
        $this->visitService = $visitService;
        $this->trackerService = $trackerService;
    }

    public function execute(ModifyQualityControlResetRequest $modifyQualityControlResetRequest, ModifyQualityControlResetResponse $modifyQualityControlResetResponse){

        try{
            $visitContext = $this->visitService->getVisitContext($modifyQualityControlResetRequest->visitId);
            $studyName = $visitContext['visit_type']['visit_group']['study_name'];

            $this->checkAuthorization($modifyQualityControlResetRequest->currentUserId, $modifyQualityControlResetRequest->visitId);
            $this->visitService->resetQc($modifyQualityControlResetRequest->visitId);
            $reviewStatusEntity = $this->visitService->getReviewStatus($modifyQualityControlResetRequest->visitId, $studyName);

            if( ! in_array($reviewStatusEntity['review_status'], array(Constants::REVIEW_STATUS_NOT_DONE, Constants::REVIEW_STATUS_NOT_NEEDED) )) {
                throw new GaelOBadRequestException("Can't reset QC with review started");
            }

            $actionDetails = [];

            $this->trackerService->writeAction(
                $modifyQualityControlResetRequest->currentUserId,
                Constants::ROLE_CONTROLER,
                $studyName,
                $modifyQualityControlResetRequest->visitId,
                Constants::TRACKER_QUALITY_CONTROL,
                $actionDetails
            );

            $modifyQualityControlResetResponse->status = 200;
            $modifyQualityControlResetResponse->statusText = 'OK';

        } catch (GaelOException $e){

            $modifyQualityControlResetResponse->body = $e->getErrorBody();
            $modifyQualityControlResetResponse->status = $e->statusCode;
            $modifyQualityControlResetResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
        }

    }

    public function checkAuthorization(int $userId, int $visitId){
        $this->authorizationVisitService->setCurrentUserAndRole($userId, Constants::ROLE_SUPERVISOR);
        $this->authorizationVisitService->setVisitId($visitId);
        if ( ! $this->authorizationVisitService->isVisitAllowed()){
            throw new GaelOForbiddenException();
        }

    }
}
