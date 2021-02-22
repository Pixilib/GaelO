<?php

namespace App\GaelO\UseCases\DeleteReviewForm;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use Exception;

class DeleteReviewForm {
    public function __construct(){

    }

    public function execute(DeleteReviewFormRequest $deleteReviewFormRequest, DeleteReviewFormResponse $deleteReviewFormResponse){

        try{

            if(empty($deleteReviewFormRequest->reason)){
                throw new GaelOBadRequestException("Reason must be specified");
            }

            //Check form ID is review form (not local + check authoriation supervison in current study)

            $this->checkAuthorization($deleteInvestigatorFormRequest->currentUserId);

            //Delete review via service review

            $actionDetails = [
                'modality' => $visitContext['visit_type']['visit_group']['modality'],
                'visit_type' => $visitContext['visit_type']['name'],
                'patient_code' => $visitContext['patient_code'],
                'id_review' => $deleteReviewFormRequest->reviewId,
                'reason' => $deleteReviewFormRequest->reason
            ];

            $this->trackerRepositoryInterface->writeAction(
                $deleteReviewFormRequest->currentUserId,
                Constants::ROLE_SUPERVISOR,
                $studyName,
                $deleteInvestigatorFormRequest->visitId,
                Constants::TRACKER_DELETE_INVESTIGATOR_FORM,
                $actionDetails);

            //send Email notification to review owner
            $this->mailServices->sendDeleteFormMessage(false,
                $investigatorFormEntity['user_id'],
                $studyName,
                $visitContext['patient_code'],
                $visitContext['visit_type']['name'] );

            $deleteReviewFormResponse->status = 200;
            $deleteReviewFormResponse->statusText =  'OK';

        } catch (GaelOException $e){

            $deleteReviewFormResponse->body = $e->getErrorBody();
            $deleteReviewFormResponse->status = $e->statusCode;
            $deleteReviewFormResponse->statusText =  $e->statusText;

        } catch (Exception $e){
            throw $e;
        }

    }

    private function checkAuthorization(){
        //SUperviseur seulement
    }
}
