<?php

namespace App\GaelO\UseCases\ModifyQualityControl;

use App\GaelO\Constants\Constants;
use App\GaelO\Constants\Enums\InvestigatorFormStateEnum;
use App\GaelO\Constants\Enums\QualityControlStateEnum;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use App\GaelO\Services\VisitService;
use Exception;

class ModifyQualityControl
{

    private AuthorizationVisitService $authorizationVisitService;
    private VisitService $visitService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(AuthorizationVisitService $authorizationVisitService, VisitService $visitService, TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->visitService = $visitService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function execute(ModifyQualityControlRequest $modifyQualityControlRequest, ModifyQualityControlResponse $modifyQualityControlResponse)
    {

        try {

            $visitId = $modifyQualityControlRequest->visitId;
            $currentUserId = $modifyQualityControlRequest->currentUserId;

            $this->visitService->setVisitId($visitId);
            $this->visitService->setCurrentUserId($currentUserId);
            $visitContext = $this->visitService->getVisitContext();

            $studyName = $visitContext['patient']['study_name'];
            $patientId = $visitContext['patient']['id'];
            $visitType = $visitContext['visit_type']['name'];
            $visitGroupName = $visitContext['visit_type']['visit_group']['name'];
            $visitModality = $visitContext['visit_type']['visit_group']['modality'];
            $localFormNeeded = $visitContext['state_investigator_form'] !== InvestigatorFormStateEnum::NOT_NEEDED->value;

            if ($modifyQualityControlRequest->studyName !== $studyName) {
                throw new GaelOForbiddenException("Should be called from original study");
            }

            $this->checkAuthorization($currentUserId, $visitId, $studyName, $visitContext);

            if ($modifyQualityControlRequest->stateQc === QualityControlStateEnum::ACCEPTED->value) {
                if ($localFormNeeded && !$modifyQualityControlRequest->formQc) {
                    throw new GaelOBadRequestException('Form should be accepted to Accept QC');
                }
                if (!$modifyQualityControlRequest->imageQc) {
                    throw new GaelOBadRequestException('Image should be accepted to Accept QC');
                }
            }

            if ($localFormNeeded && !$modifyQualityControlRequest->formQc && empty($modifyQualityControlRequest->formQcComment)) {
                throw new GaelOBadRequestException('For Refused Form, a reason must be specified');
            }

            if (!$modifyQualityControlRequest->imageQc && empty($modifyQualityControlRequest->imageQcComment)) {
                throw new GaelOBadRequestException('For Refused Image, a reason must be specified');
            }

            $this->visitService->editQc(
                $modifyQualityControlRequest->stateQc,
                $currentUserId,
                $modifyQualityControlRequest->imageQc,
                $modifyQualityControlRequest->formQc,
                $modifyQualityControlRequest->imageQcComment,
                $modifyQualityControlRequest->formQcComment
            );


            $actionDetails = [
                'patient_id' => $patientId,
                'visit_type' => $visitType,
                'visit_group_name' => $visitGroupName,
                'vist_group_modality' => $visitModality,
                'form_accepted' => $modifyQualityControlRequest->formQc,
                'image_accepted' => $modifyQualityControlRequest->imageQc,
                'form_comment' => $modifyQualityControlRequest->formQcComment,
                'image_comment' => $modifyQualityControlRequest->imageQcComment,
                'qc_decision' => $modifyQualityControlRequest->stateQc
            ];

            $this->trackerRepositoryInterface->writeAction(
                $currentUserId,
                Constants::ROLE_CONTROLLER,
                $studyName,
                $visitId,
                Constants::TRACKER_QUALITY_CONTROL,
                $actionDetails
            );

            $modifyQualityControlResponse->status = 200;
            $modifyQualityControlResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {

            $modifyQualityControlResponse->body = $e->getErrorBody();
            $modifyQualityControlResponse->status = $e->statusCode;
            $modifyQualityControlResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $userId, int $visitId, string $studyName, array $visitContext): void
    {
        //Check user has controller role in the visit
        $this->authorizationVisitService->setUserId($userId);
        $this->authorizationVisitService->setVisitId($visitId);
        $this->authorizationVisitService->setStudyName($studyName);
        $this->authorizationVisitService->setVisitContext($visitContext);

        if (!$this->authorizationVisitService->isVisitAllowed(Constants::ROLE_CONTROLLER)) {
            throw new GaelOForbiddenException();
        }
    }
}
