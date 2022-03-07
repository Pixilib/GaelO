<?php

namespace App\GaelO\UseCases\GetInvestigatorFormsMetadataFromVisitType;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Repositories\VisitGroupRepository;
use App\GaelO\Repositories\VisitTypeRepository;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use App\GaelO\Services\FormService\FormService;
use Exception;

class GetInvestigatorFormsMetadataFromVisitType
{
    private VisitTypeRepository $visitTypeRepository;
    private VisitGroupRepository $visitGroupRepository;
    private FormService $formService;
    private AuthorizationStudyService $authorizationStudyService;

    public function __construct(AuthorizationStudyService $authorizationStudyService, VisitTypeRepository $visitTypeRepository, VisitGroupRepository $visitGroupRepository, FormService $formService)
    {
        $this->authorizationStudyService = $authorizationStudyService;
        $this->visitTypeRepository = $visitTypeRepository;
        $this->visitGroupRepository = $visitGroupRepository;
        $this->formService = $formService;
    }

    public function execute(GetInvestigatorFormsMetadataFromVisitTypeRequest $getInvestigatorFormsMetadataFromVisitTypeRequest, GetInvestigatorFormsMetadataFromVisitTypeResponse $getInvestigatorFormsMetadataFromVisitTypeResponse)
    {
        try{

            $studyName = $getInvestigatorFormsMetadataFromVisitTypeRequest->studyName;
            //SK ICI VERIFIER QUE VISIT TYPE EST BIEN DANS UNE STUDY PERMISE
            $this->checkAuthorization($getInvestigatorFormsMetadataFromVisitTypeRequest->currentUserId, $studyName);

            $visitTypeEntity = $this->visitTypeRepository->find($getInvestigatorFormsMetadataFromVisitTypeRequest->visitTypeId);

            $visitGroupEntity = $this->visitGroupRepository->find($visitTypeEntity['visit_group_id']);

            $abstractStudyRules = $this->formService->getSpecificStudiesRules($studyName, $visitGroupEntity['modality'], $visitTypeEntity['name']);

            $investigatorRules = $abstractStudyRules->getInvestigatorValidationRules();

            $getInvestigatorFormsMetadataFromVisitTypeResponse->body = $investigatorRules;
            $getInvestigatorFormsMetadataFromVisitTypeResponse->status = 200;
            $getInvestigatorFormsMetadataFromVisitTypeResponse->statusText = 'OK';

        } catch (GaelOException $e) {

            $getInvestigatorFormsMetadataFromVisitTypeResponse->body = $e->getErrorBody();
            $getInvestigatorFormsMetadataFromVisitTypeResponse->status = $e->statusCode;
            $getInvestigatorFormsMetadataFromVisitTypeResponse->statusText = $e->statusText;

        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $userId, string $studyName)
    {
        $this->authorizationStudyService->setUserId($userId);
        $this->authorizationStudyService->setStudyName($studyName);
        if (!$this->authorizationStudyService->isAllowedStudy(Constants::ROLE_SUPERVISOR)) {
            throw new GaelOForbiddenException();
        };
    }
}
