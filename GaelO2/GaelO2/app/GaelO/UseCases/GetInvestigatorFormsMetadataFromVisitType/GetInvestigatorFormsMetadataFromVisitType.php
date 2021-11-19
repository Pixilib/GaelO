<?php

namespace App\GaelO\UseCases\GetInvestigatorFormsMetadataFromVisitType;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Repositories\VisitGroupRepository;
use App\GaelO\Repositories\VisitTypeRepository;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class GetInvestigatorFormsMetadataFromVisitType
{
    private VisitTypeRepository $visitTypeRepository;
    private VisitGroupRepository $visitGroupRepository;
    private FrameworkInterface $frameworkInterface;
    private AuthorizationUserService $authorizationUserService;

    public function __construct(AuthorizationUserService $authorizationUserService, VisitTypeRepository $visitTypeRepository, VisitGroupRepository $visitGroupRepository, FrameworkInterface $frameworkInterface)
    {
        $this->authorizationUserService = $authorizationUserService;
        $this->visitTypeRepository = $visitTypeRepository;
        $this->visitGroupRepository = $visitGroupRepository;
        $this->frameworkInterface = $frameworkInterface;
    }

    public function execute(GetInvestigatorFormsMetadataFromVisitTypeRequest $getInvestigatorFormsMetadataFromVisitTypeRequest, GetInvestigatorFormsMetadataFromVisitTypeResponse $getInvestigatorFormsMetadataFromVisitTypeResponse)
    {
        try{

            $studyName = $getInvestigatorFormsMetadataFromVisitTypeRequest->studyName;
            //SK ICI VERIFIER QUE VISIT TYPE EST BIEN DANS UNE STUDY PERMISE
            $this->checkAuthorization($getInvestigatorFormsMetadataFromVisitTypeRequest->currentUserId, $studyName);

            $visitTypeEntity = $this->visitTypeRepository->find($getInvestigatorFormsMetadataFromVisitTypeRequest->visitTypeId);

            $visitGroupEntity = $this->visitGroupRepository->find($visitTypeEntity['visit_group_id']);

            $abstractStudyRules = $this->frameworkInterface->make('\App\GaelO\Services\SpecificStudiesRules\\' . $studyName . '_' . $visitGroupEntity['modality'] . '_' . $visitTypeEntity['name']);

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
        $this->authorizationUserService->setUserId($userId);
        if (!$this->authorizationUserService->isRoleAllowed(Constants::ROLE_SUPERVISOR, $studyName)) {
            throw new GaelOForbiddenException();
        };
    }
}
