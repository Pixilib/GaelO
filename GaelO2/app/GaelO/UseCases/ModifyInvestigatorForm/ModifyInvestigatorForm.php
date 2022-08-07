<?php

namespace App\GaelO\UseCases\ModifyInvestigatorForm;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use App\GaelO\Services\FormService\InvestigatorFormService;
use Exception;

class ModifyInvestigatorForm
{

    private AuthorizationVisitService $authorizationVisitService;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private InvestigatorFormService $investigatorFormService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(AuthorizationVisitService $authorizationVisitService, VisitRepositoryInterface $visitRepositoryInterface, InvestigatorFormService $investigatorFormService, TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->investigatorFormService = $investigatorFormService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function execute(ModifyInvestigatorFormRequest $modifyInvestigatorFormRequest, ModifyInvestigatorFormResponse $modifyInvestigatorFormResponse)
    {

        try {

            if (!isset($modifyInvestigatorFormRequest->validated) || !isset($modifyInvestigatorFormRequest->visitId)) {
                throw new GaelOBadRequestException('VisitID and Validated Status are mandatory');
            }

            $visitContext = $this->visitRepositoryInterface->getVisitContext($modifyInvestigatorFormRequest->visitId);
            $studyName = $visitContext['patient']['study_name'];
            $visitId = $visitContext['id'];

            $currentUserId = $modifyInvestigatorFormRequest->currentUserId;
            $data = $modifyInvestigatorFormRequest->data;
            $validated = $modifyInvestigatorFormRequest->validated;

            $this->checkAuthorization(
                $currentUserId,
                $visitId,
                $visitContext['state_investigator_form'],
                $studyName
            );

            $this->investigatorFormService->setCurrentUserId($currentUserId);
            $this->investigatorFormService->setVisitContextAndStudy($visitContext, $studyName);
            $localReviewId = $this->investigatorFormService->updateInvestigatorForm($data, $validated);

            $actionDetails = [
                'raw_data' => $data,
                'validated' => $validated
            ];

            $this->trackerRepositoryInterface->writeAction($currentUserId, Constants::ROLE_INVESTIGATOR, $studyName, $visitId, Constants::TRACKER_MODIFY_INVESTIGATOR_FORM, $actionDetails);

            $modifyInvestigatorFormResponse->body = ['id' => $localReviewId];
            $modifyInvestigatorFormResponse->status = 200;
            $modifyInvestigatorFormResponse->statusText =  'OK';
        } catch (GaelOException $e) {

            $modifyInvestigatorFormResponse->body = $e->getErrorBody();
            $modifyInvestigatorFormResponse->status = $e->statusCode;
            $modifyInvestigatorFormResponse->statusText =  $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }


    private function checkAuthorization(int $currentUserId, int $visitId, string $visitInvestigatorFormStatus, string $studyName)
    {

        if (in_array($visitInvestigatorFormStatus, [Constants::INVESTIGATOR_FORM_DONE])) {
            throw new GaelOForbiddenException();
        };

        if ($visitInvestigatorFormStatus === Constants::INVESTIGATOR_FORM_NOT_NEEDED) {
            //Can't modify an investigator form if not expected to have
            throw new GaelOForbiddenException();
        };

        $this->authorizationVisitService->setUserId($currentUserId);
        $this->authorizationVisitService->setVisitId($visitId);
        $this->authorizationVisitService->setStudyName($studyName);

        if (!$this->authorizationVisitService->isVisitAllowed(Constants::ROLE_INVESTIGATOR)) {
            throw new GaelOForbiddenException();
        }
    }
}
