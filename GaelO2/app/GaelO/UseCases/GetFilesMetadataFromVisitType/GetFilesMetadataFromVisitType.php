<?php

namespace App\GaelO\UseCases\GetFilesMetadataFromVisitType;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Repositories\VisitTypeRepository;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use App\GaelO\Services\GaelOStudiesService\AbstractGaelOStudy;
use Exception;

class GetFilesMetadataFromVisitType
{
    private VisitTypeRepository $visitTypeRepository;
    private AuthorizationStudyService $authorizationStudyService;

    public function __construct(AuthorizationStudyService $authorizationStudyService, VisitTypeRepository $visitTypeRepository)
    {
        $this->authorizationStudyService = $authorizationStudyService;
        $this->visitTypeRepository = $visitTypeRepository;
    }

    public function execute(GetFilesMetadataFromVisitTypeRequest $getFilesMetadataFromVisitTypeRequest, GetFilesMetadataFromVisitTypeResponse $getFilesMetadataFromVisitTypeResponse)
    {
        try {

            $studyName = $getFilesMetadataFromVisitTypeRequest->studyName;

            $this->checkAuthorization($getFilesMetadataFromVisitTypeRequest->currentUserId, $studyName);

            $visitTypeEntity = $this->visitTypeRepository->find($getFilesMetadataFromVisitTypeRequest->visitTypeId, true);
            $originalStudyName = $visitTypeEntity['visit_group']['study_name'];
            $visitGroupEntity = $visitTypeEntity['visit_group'];

            //Check that the requested study name is an original or ancillary study of this visit type
            if (!AuthorizationStudyService::isOriginalOrAncillaryStudyOf($studyName, $originalStudyName)) {
                throw new GaelOForbiddenException('Forbidden acces to this Visit Type');
            }

            $visitFiles = [];

            try {
                $studyRule = AbstractGaelOStudy::getSpecificStudyObject($studyName);
                $visitRules = $studyRule->getSpecificVisitRules($visitGroupEntity['name'], $visitTypeEntity['name']);
                $visitFiles = $visitRules->getAssociatedFilesVisit();
            } catch (GaelOException $e) {}

            $getFilesMetadataFromVisitTypeResponse->body = $visitFiles;
            $getFilesMetadataFromVisitTypeResponse->status = 200;
            $getFilesMetadataFromVisitTypeResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $getFilesMetadataFromVisitTypeResponse->body = $e->getErrorBody();
            $getFilesMetadataFromVisitTypeResponse->status = $e->statusCode;
            $getFilesMetadataFromVisitTypeResponse->statusText = $e->statusText;
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
