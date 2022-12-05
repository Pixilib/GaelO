<?php

namespace App\GaelO\UseCases\GetStudyVisitTypes;

use App\GaelO\Constants\Constants;
use App\GaelO\Entities\VisitGroupEntity;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Entities\VisitTypeEntity;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitTypeRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use Exception;

class GetStudyVisitTypes
{

    private AuthorizationStudyService $authorizationStudyService;
    private StudyRepositoryInterface $studyRepositoryInterface;
    private VisitTypeRepositoryInterface $visitTypeRepositoryInterface;

    public function __construct(
        StudyRepositoryInterface $studyRepositoryInterface,
        VisitTypeRepositoryInterface $visitTypeRepositoryInterface,
        AuthorizationStudyService $authorizationStudyService)
    {

        $this->authorizationStudyService = $authorizationStudyService;
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->visitTypeRepositoryInterface = $visitTypeRepositoryInterface;
    }

    public function execute(GetStudyVisitTypesRequest $getStudyVisitTypesRequest, GetStudyVisitTypesResponse $getStudyVisitTypesResponse): void
    {

        try {
            $currentUserId = $getStudyVisitTypesRequest->currentUserId;
            $studyName = $getStudyVisitTypesRequest->studyName;

            $this->checkAuthorization($currentUserId, $studyName);

            $studyEntity = $this->studyRepositoryInterface->find($studyName);
            $originalStudyName = $studyEntity->getOriginalStudyName();

            $visitTypes = $this->visitTypeRepositoryInterface->getVisitTypesOfStudy($originalStudyName);

            $studyDetailResponse = [];

            foreach ($visitTypes as $visitType) {
                $visitTypeEntity = VisitTypeEntity::fillFromDBReponseArray($visitType);
                $visitTypeEntity->setVisitGroup(VisitGroupEntity::fillFromDBReponseArray($visitType['visit_group']));
                $studyDetailResponse[] = $visitTypeEntity;
            }

            $getStudyVisitTypesResponse->body = $studyDetailResponse;
            $getStudyVisitTypesResponse->status = 200;
            $getStudyVisitTypesResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $getStudyVisitTypesResponse->body = $e->getErrorBody();
            $getStudyVisitTypesResponse->status = $e->statusCode;
            $getStudyVisitTypesResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $userId, string $studyName): void
    {
        $this->authorizationStudyService->setUserId($userId);
        $this->authorizationStudyService->setStudyName($studyName);
        if (!$this->authorizationStudyService->isAllowedStudy(Constants::ROLE_SUPERVISOR)) {
            throw new GaelOForbiddenException();
        }
    }
}
