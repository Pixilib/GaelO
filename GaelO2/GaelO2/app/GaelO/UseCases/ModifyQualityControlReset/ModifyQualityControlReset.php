<?php

namespace App\GaelO\UseCases\ModifyQualityControlReset;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use App\GaelO\Services\VisitService;
use Exception;

class ModifyQualityControlReset
{

    private AuthorizationVisitService $authorizationVisitService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private VisitService $visitService;

    public function __construct(AuthorizationVisitService $authorizationVisitService, VisitService $visitService, TrackerRepositoryInterface $trackerRepositoryInterface)
    {

        $this->authorizationVisitService = $authorizationVisitService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->visitService = $visitService;
    }

    public function execute(ModifyQualityControlResetRequest $modifyQualityControlResetRequest, ModifyQualityControlResetResponse $modifyQualityControlResetResponse)
    {

        try {

            $this->visitService->setVisitId($modifyQualityControlResetRequest->visitId);
            $visitContext = $this->visitService->getVisitContext();
            $studyName = $visitContext['patient']['study_name'];

            $this->checkAuthorization($modifyQualityControlResetRequest->currentUserId, $modifyQualityControlResetRequest->visitId, $studyName);

            if (empty($modifyQualityControlResetRequest->reason)) {
                throw new GaelOBadRequestException("Can't reset QC without reason");
            }

            $reviewStatusEntity = $this->visitService->getReviewStatus($studyName);

            if (!in_array($reviewStatusEntity['review_status'], array(Constants::REVIEW_STATUS_NOT_DONE, Constants::REVIEW_STATUS_NOT_NEEDED))) {
                throw new GaelOBadRequestException("Can't reset QC with review started");
            }

            $this->visitService->resetQc($modifyQualityControlResetRequest->visitId);

            $actionDetails = [
                'reason' => $modifyQualityControlResetRequest->reason
            ];

            $this->trackerRepositoryInterface->writeAction(
                $modifyQualityControlResetRequest->currentUserId,
                Constants::ROLE_CONTROLLER,
                $studyName,
                $modifyQualityControlResetRequest->visitId,
                Constants::TRACKER_RESET_QC,
                $actionDetails
            );

            $modifyQualityControlResetResponse->status = 200;
            $modifyQualityControlResetResponse->statusText = 'OK';

        } catch (GaelOException $e) {
            $modifyQualityControlResetResponse->body = $e->getErrorBody();
            $modifyQualityControlResetResponse->status = $e->statusCode;
            $modifyQualityControlResetResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function checkAuthorization(int $userId, int $visitId, string $studyName)
    {
        $this->authorizationVisitService->setUserId($userId);
        $this->authorizationVisitService->setVisitId($visitId);
        $this->authorizationVisitService->setStudyName($studyName);

        if (!$this->authorizationVisitService->isVisitAllowed(Constants::ROLE_SUPERVISOR)) {
            throw new GaelOForbiddenException();
        }
    }
}
