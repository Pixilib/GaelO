<?php

namespace App\GaelO\UseCases\GetInvestigatorFormsMetadataFromVisitType;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Repositories\VisitTypeRepository;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use App\GaelO\Services\GaelOStudiesService\AbstractGaelOStudy;
use Exception;

class GetInvestigatorFormsMetadataFromVisitType
{
    private VisitTypeRepository $visitTypeRepository;
    private AuthorizationStudyService $authorizationStudyService;

    public function __construct(AuthorizationStudyService $authorizationStudyService, VisitTypeRepository $visitTypeRepository)
    {
        $this->authorizationStudyService = $authorizationStudyService;
        $this->visitTypeRepository = $visitTypeRepository;
    }

    public function execute(GetInvestigatorFormsMetadataFromVisitTypeRequest $getInvestigatorFormsMetadataFromVisitTypeRequest, GetInvestigatorFormsMetadataFromVisitTypeResponse $getInvestigatorFormsMetadataFromVisitTypeResponse)
    {
        try {

            $studyName = $getInvestigatorFormsMetadataFromVisitTypeRequest->studyName;

            $this->checkAuthorization($getInvestigatorFormsMetadataFromVisitTypeRequest->currentUserId, $studyName);

            $visitTypeEntity = $this->visitTypeRepository->find($getInvestigatorFormsMetadataFromVisitTypeRequest->visitTypeId, true);
            $originalStudyName = $visitTypeEntity['visit_group']['study_name'];
            $visitGroupEntity = $visitTypeEntity['visit_group'];

            //Check that the requested study name is an original or ancillary study of this visit type
            if (!AuthorizationStudyService::isOrginalOrAncillaryStudyOf($studyName, $originalStudyName)) {
                throw new GaelOForbiddenException('Forbidden acces to this Visit Type');
            }
            $studyRule = AbstractGaelOStudy::getSpecificStudyObject($studyName);
            $abstractStudyRules = $studyRule->getSpecificVisitRules($visitGroupEntity['name'], $visitTypeEntity['name']);
            $investigatorRules = $abstractStudyRules::getInvestigatorValidationRules();

            $getInvestigatorFormsMetadataFromVisitTypeResponse->body = $investigatorRules;
            $getInvestigatorFormsMetadataFromVisitTypeResponse->status = 200;
            $getInvestigatorFormsMetadataFromVisitTypeResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
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
        }
    }
}
