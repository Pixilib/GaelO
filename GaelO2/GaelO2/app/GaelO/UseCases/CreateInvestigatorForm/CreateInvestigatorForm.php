<?php

namespace App\GaelO\UseCases\CreateInvestigatorForm;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use App\GaelO\Services\FormService\InvestigatorFormService;
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
            $studyName = $visitContext['patient']['study_name'];
            $isLocalFormNeeded = $visitContext['visit_type']['local_form_needed'];

            $this->checkAuthorization(
                $createInvestigatorFormRequest->currentUserId,
                $createInvestigatorFormRequest->visitId,
                $visitContext['state_investigator_form'],
                $isLocalFormNeeded,
                $studyName
            );

            $this->investigatorFormService->setCurrentUserId($createInvestigatorFormRequest->currentUserId);
            $this->investigatorFormService->setVisitContextAndStudy($visitContext, $studyName);
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

    private function checkAuthorization(int $currentUserId, int $visitId, string $visitInvestigatorFormStatus, bool $investigatorFormNeeded, string $studyName){

        if(in_array($visitInvestigatorFormStatus, [Constants::INVESTIGATOR_FORM_DRAFT, Constants::INVESTIGATOR_FORM_DONE])){
            throw new GaelOForbiddenException();
        };

        if(!$investigatorFormNeeded){
            throw new GaelOForbiddenException();
        };

        $this->authorizationVisitService->setUserId($currentUserId);
        $this->authorizationVisitService->setVisitId($visitId);
        $this->authorizationVisitService->setStudyName($studyName);

        if ( ! $this->authorizationVisitService->isVisitAllowed(Constants::ROLE_INVESTIGATOR)){
            throw new GaelOForbiddenException();
        }
    }

}
