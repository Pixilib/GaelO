<?php

namespace App\GaelO\UseCases\ModifyQualityControl;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationVisitService;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\VisitService;
use Exception;

class ModifyQualityControl {

    private AuthorizationVisitService $authorizationVisitService;
    private VisitService $visitService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private MailServices $mailServices;

    public function __construct(AuthorizationVisitService $authorizationVisitService, VisitService $visitService, TrackerRepositoryInterface $trackerRepositoryInterface, MailServices $mailServices){
        $this->authorizationVisitService = $authorizationVisitService;
        $this->visitService = $visitService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->mailServices = $mailServices;
    }

    public function execute(ModifyQualityControlRequest $modifyQualityControlRequest, ModifyQualityControlResponse $modifyQualityControlResponse){

        try{
            $this->visitService->setVisitId($modifyQualityControlRequest->visitId);
            $visitContext = $this->visitService->getVisitContext();

            $studyName = $visitContext['visit_type']['visit_group']['study_name'];
            $patientCode = $visitContext['patient']['code'];
            $visitType = $visitContext['visit_type']['name'];
            $visitModality = $visitContext['visit_type']['visit_group']['modality'];
            $centerCode = $visitContext['patient']['center_code'];
            $creatorId = $visitContext['creator_user_id'];
            $localFormNeeded = $visitContext['visit_type']['local_form_needed'];

            $this->checkAuthorization($modifyQualityControlRequest->currentUserId, $modifyQualityControlRequest->visitId);

            if($modifyQualityControlRequest->stateQc === Constants::QUALITY_CONTROL_ACCEPTED){
                if($localFormNeeded && ! $modifyQualityControlRequest->formQc){
                    throw new GaelOBadRequestException('Form should be accepted to Accept QC');
                }
                if(!$modifyQualityControlRequest->imageQc){
                    throw new GaelOBadRequestException('Image should be accepted to Accept QC');
                }

            }

            if( $localFormNeeded && ! $modifyQualityControlRequest->formQc && empty($modifyQualityControlRequest->formQcComment)){
                throw new GaelOBadRequestException('For Refused Form, a reason must be specified');
            }

            if( ! $modifyQualityControlRequest->imageQc && empty($modifyQualityControlRequest->imageQcComment)){
                throw new GaelOBadRequestException('For Refused Image, a reason must be specified');
            }

            $this->visitService->editQc(
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

            $this->trackerRepositoryInterface->writeAction(
                $modifyQualityControlRequest->currentUserId,
                Constants::ROLE_CONTROLLER,
                $studyName,
                $modifyQualityControlRequest->visitId,
                Constants::TRACKER_QUALITY_CONTROL,
                $actionDetails
            );

            $this->mailServices->sendQcDecisionMessage(
                $modifyQualityControlRequest->visitId,
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
                $modifyQualityControlRequest->formQcComment ?? 'None',
                $modifyQualityControlRequest->imageQcComment ?? 'None'
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
        $this->authorizationVisitService->setCurrentUserAndRole($userId, Constants::ROLE_CONTROLLER);
        $this->authorizationVisitService->setVisitId($visitId);
        if ( ! $this->authorizationVisitService->isVisitAllowed() ){
            throw new GaelOForbiddenException();
        }

    }
}
