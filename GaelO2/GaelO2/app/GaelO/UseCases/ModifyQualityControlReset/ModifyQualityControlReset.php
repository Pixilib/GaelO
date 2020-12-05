<?php

namespace App\GaelO\UseCases\ModifyQualityControlReset;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationVisitService;
use App\GaelO\Services\TrackerService;
use Exception;

class ModifyQualityControlReset{

    private AuthorizationVisitService $authorizationVisitService;
    private TrackerService $trackerService;

    public function __construct(AuthorizationVisitService $authorizationVisitService, PersistenceInterface $persistenceInterface, TrackerService $trackerService){

        $this->authorizationVisitService = $authorizationVisitService;
        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService = $trackerService;
    }

    public function execute(ModifyQualityControlResetRequest $modifyQualityControlResetRequest, ModifyQualityControlResetResponse $modifyQualityControlResetResponse){

        try{
            //SK Recuperer la study name du princepts de la visit
            //et récupérer le review status
            //ResetQC que si review status est not DOne
            $visitContext = $this->persistenceInterface->getVisitContext($modifyQualityControlResetRequest->visitId);

            $this->checkAuthorization($modifyQualityControlResetRequest->currentUserId, $modifyQualityControlResetRequest->visitId);
            $this->persistenceInterface->resetQc($modifyQualityControlResetRequest->visitId);

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
