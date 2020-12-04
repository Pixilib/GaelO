<?php

namespace App\GaelO\UseCases\ModifyQualityControl;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Services\AuthorizationVisitService;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\TrackerService;
use App\GaelO\Services\VisitService;
use Exception;

class ModifyQualityControl {

    private AuthorizationVisitService $authorizationService;
    private VisitService $visitService;
    private TrackerService $trackerService;
    private MailServices $mailServices;

    public function __construct(AuthorizationVisitService $authorizationVisitService, VisitService $visitService, TrackerService $trackerService, MailServices $mailServices){
        $this->authorizationVisitService = $authorizationVisitService;
        $this->visitService = $visitService;
        $this->trackerService = $trackerService;
        $this->mailServices = $mailServices;
    }

    public function execute(ModifyQualityControlRequest $modifyQualityControlRequest, ModifyQualityControlResponse $modifyQualityControlResponse){

        try{
            $visitContext = $this->visitService->getVisitContext($modifyQualityControlRequest->visitId);

            $studyName = $visitContext['visit_type']['visit_group']['study_name'];
            $patientCode = $visitContext['patient']['center_code'];
            $visitType = $visitContext['visit_type']['name'];
            $visitModality = $visitContext['visit_type']['visit_group']['modality'];
            $centerCode = $visitContext['patient']['center_code'];
            $creatorId = $visitContext['creator_user_id'];

            $this->checkAuthorization($modifyQualityControlRequest->currentUserId, $modifyQualityControlRequest->visitId);

            $this->visitService->editQc(
                    $modifyQualityControlRequest->visitId,
                    $modifyQualityControlRequest->stateQc,
                    $modifyQualityControlRequest->currentUserId,
                    $modifyQualityControlRequest->imageQc,
                    $modifyQualityControlRequest->formQc,
                    $modifyQualityControlRequest->imageQcComment,
                    $modifyQualityControlRequest->formQcComment
            );


            $actionDetails = [
                'patient_code'=>$patientCode,
                'visit_type'=>$visitType,
                'vist_group_modality'=>$visitModality,
                'form_accepted'=>$modifyQualityControlRequest->formQc,
                'image_accepted'=>$modifyQualityControlRequest->imageQc,
                'form_comment'=>$modifyQualityControlRequest->formQcComment,
                'image_comment'=>$modifyQualityControlRequest->imageQcComment,
                'qc_decision'=>$modifyQualityControlRequest->stateQc
            ];

            $this->trackerService->writeAction(
                $modifyQualityControlRequest->currentUserId,
                Constants::ROLE_CONTROLER,
                $studyName,
                $modifyQualityControlRequest->visitId,
                Constants::TRACKER_QUALITY_CONTROL,
                $actionDetails
            );

            $this->mailServices->sendQcDecisionMessage(
                $creatorId,
                $modifyQualityControlRequest->currentUserId,
                $studyName,
                $centerCode,
                $modifyQualityControlRequest->stateQc,
                $patientCode,
                $visitModality,
                $visitType,
                $modifyQualityControlRequest->formQc ? 'Accepted ' : 'Refused',
                $modifyQualityControlRequest->imageQc ? 'Accepted ' : 'Refused',
                $modifyQualityControlRequest->formQcComment,
                $modifyQualityControlRequest->imageQcComment
            );

            $modifyQualityControlResponse->status = 200;
            $modifyQualityControlResponse->statusText = 'OK';

        }catch(GaelOException $e){

            $modifyQualityControlResponse->body = $e->getErrorBody();
            $modifyQualityControlResponse->status = $e->statusCode;
            $modifyQualityControlResponse->statusText = $e->statusText;

        }catch (Exception $e){
            throw $e;
        }
    }

    private function checkAuthorization(int $userId, int $visitId) : void {
        //Check user has controller role in the visit
        $this->authorizationVisitService->setCurrentUserAndRole($userId, Constants::ROLE_CONTROLER);
        $this->authorizationVisitService->setVisitId($visitId);
        if ( ! $this->authorizationVisitService->isVisitAllowed() ){
            throw new GaelOForbiddenException();
        }

    }
}
