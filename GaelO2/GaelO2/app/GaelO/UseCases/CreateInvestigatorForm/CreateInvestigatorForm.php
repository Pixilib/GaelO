<?php

namespace App\GaelO\UseCases\CreateInvestigatorForm;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\TrackerRepositoryInterface;
use App\GaelO\Interfaces\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationVisitService;
use App\GaelO\Services\InvestigatorFormService;
use Exception;


class CreateInvestigatorForm {

    private AuthorizationVisitService $authorizationVisitService;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private InvestigatorFormService $investigatorFormService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(
        AuthorizationVisitService $authorizationVisitService,
        VisitRepositoryInterface $visitRepositoryInterface,
        InvestigatorFormService $investigatorFormService,
        TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->investigatorFormService = $investigatorFormService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function execute(CreateInvestigatorFormRequest $createInvestigatorFormRequest, CreateInvestigatorFormResponse $createInvestigatorFormResponse){

        try{

            if( ! isset($createInvestigatorFormRequest->validated) || !isset($createInvestigatorFormRequest->visitId) ){
                throw new GaelOBadRequestException('VisitID and Validated Status are mandatory');
            }

            $visitContext = $this->visitRepositoryInterface->getVisitContext($createInvestigatorFormRequest->visitId);
            $modality = $visitContext['visit_type']['visit_group']['modality'];
            $studyName = $visitContext['visit_type']['visit_group']['study_name'];
            $visitType = $visitContext['visit_type']['name'];
            $isLocalFormNeeded = $visitContext['visit_type']['local_form_needed'];
            $patientCode = $visitContext['patient_code'];
            $uploaderId = $visitContext['creator_user_id'];

            $this->checkAuthorization(
                $createInvestigatorFormRequest->currentUserId,
                $createInvestigatorFormRequest->visitId,
                $visitContext['state_investigator_form'],
                $isLocalFormNeeded
            );

            $this->investigatorFormService->setCurrentUserId($createInvestigatorFormRequest->currentUserId);
            $this->investigatorFormService->setVisitContext($visitContext);
            $this->investigatorFormService->saveInvestigatorForm($createInvestigatorFormRequest->data, $createInvestigatorFormRequest->validated);

            $actionDetails = [
                'raw_data' => $createInvestigatorFormRequest->data,
                'validated' => $createInvestigatorFormRequest->validated
            ];

            $this->trackerRepositoryInterface->writeAction($createInvestigatorFormRequest->currentUserId, Constants::ROLE_INVESTIGATOR, $studyName, $createInvestigatorFormRequest->visitId, Constants::TRACKER_SAVE_INVESTIGATOR_FORM, $actionDetails);

            $createInvestigatorFormResponse->status = 201;
            $createInvestigatorFormResponse->statusText =  'Created';

        } catch (GaelOException $e){

            $createInvestigatorFormResponse->body = $e->getErrorBody();
            $createInvestigatorFormResponse->status = $e->statusCode;
            $createInvestigatorFormResponse->statusText =  $e->statusText;

        }catch (Exception $e){
            throw $e;
        }

    }

    private function checkAuthorization(int $currentUserId, int $visitId, string $visitInvestigatorFormStatus, bool $investigatorFormNeeded){

        if(in_array($visitInvestigatorFormStatus, [Constants::INVESTIGATOR_FORM_DRAFT, Constants::INVESTIGATOR_FORM_DONE])){
            throw new GaelOForbiddenException();
        };

        if(!$investigatorFormNeeded){
            throw new GaelOForbiddenException();
        };

        $this->authorizationVisitService->setCurrentUserAndRole($currentUserId, Constants::ROLE_INVESTIGATOR);
        $this->authorizationVisitService->setVisitId($visitId);
        if ( ! $this->authorizationVisitService->isVisitAllowed()){
            throw new GaelOForbiddenException();
        }
    }

}
