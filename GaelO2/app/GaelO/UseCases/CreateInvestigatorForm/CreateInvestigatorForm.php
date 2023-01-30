<?php

namespace App\GaelO\UseCases\CreateInvestigatorForm;

use App\GaelO\Constants\Constants;
use App\GaelO\Constants\Enums\InvestigatorFormStateEnum;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use App\GaelO\Services\FormService\InvestigatorFormService;
use Exception;


class CreateInvestigatorForm
{

    private AuthorizationVisitService $authorizationVisitService;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private InvestigatorFormService $investigatorFormService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(
        AuthorizationVisitService $authorizationVisitService,
        VisitRepositoryInterface $visitRepositoryInterface,
        InvestigatorFormService $investigatorFormService,
        TrackerRepositoryInterface $trackerRepositoryInterface
    ) {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->investigatorFormService = $investigatorFormService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function execute(CreateInvestigatorFormRequest $createInvestigatorFormRequest, CreateInvestigatorFormResponse $createInvestigatorFormResponse)
    {

        try {

            if (!isset($createInvestigatorFormRequest->validated) || !isset($createInvestigatorFormRequest->visitId)) {
                throw new GaelOBadRequestException('VisitID and Validated Status are mandatory');
            }

            $visitContext = $this->visitRepositoryInterface->getVisitContext($createInvestigatorFormRequest->visitId);
            $studyName = $visitContext['patient']['study_name'];
            $stateInvestigatorForm = $visitContext['state_investigator_form'];

            $this->checkAuthorization(
                $createInvestigatorFormRequest->currentUserId,
                $createInvestigatorFormRequest->visitId,
                $stateInvestigatorForm,
                $studyName,
                $visitContext
            );

            $this->investigatorFormService->setCurrentUserId($createInvestigatorFormRequest->currentUserId);
            $this->investigatorFormService->setVisitContextAndStudy($visitContext, $studyName);
            $createdFormId = $this->investigatorFormService->createForm($createInvestigatorFormRequest->data, $createInvestigatorFormRequest->validated);

            $actionDetails = [
                'raw_data' => $createInvestigatorFormRequest->data,
                'validated' => $createInvestigatorFormRequest->validated
            ];

            $this->trackerRepositoryInterface->writeAction($createInvestigatorFormRequest->currentUserId, Constants::ROLE_INVESTIGATOR, $studyName, $createInvestigatorFormRequest->visitId, Constants::TRACKER_SAVE_INVESTIGATOR_FORM, $actionDetails);

            $createInvestigatorFormResponse->body = ['id' => $createdFormId];
            $createInvestigatorFormResponse->status = 201;
            $createInvestigatorFormResponse->statusText =  'Created';
        } catch (AbstractGaelOException $e) {
            $createInvestigatorFormResponse->body = $e->getErrorBody();
            $createInvestigatorFormResponse->status = $e->statusCode;
            $createInvestigatorFormResponse->statusText =  $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $currentUserId, int $visitId, string $visitInvestigatorFormStatus, string $studyName, array $visitContext)
    {

        if (in_array($visitInvestigatorFormStatus, [InvestigatorFormStateEnum::DRAFT->value, InvestigatorFormStateEnum::DONE->value])) {
            throw new GaelOForbiddenException();
        }

        if ($visitInvestigatorFormStatus === InvestigatorFormStateEnum::NOT_NEEDED->value) {
            //No investigator form creation if not expected to have one
            throw new GaelOForbiddenException();
        }

        $this->authorizationVisitService->setUserId($currentUserId);
        $this->authorizationVisitService->setVisitId($visitId);
        $this->authorizationVisitService->setStudyName($studyName);
        $this->authorizationVisitService->setVisitContext($visitContext);

        if (!$this->authorizationVisitService->isVisitAllowed(Constants::ROLE_INVESTIGATOR)) {
            throw new GaelOForbiddenException();
        }
    }
}
