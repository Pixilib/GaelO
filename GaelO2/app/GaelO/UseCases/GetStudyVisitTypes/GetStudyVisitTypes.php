<?php

namespace App\GaelO\UseCases\GetStudyVisitTypes;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Entities\VisitTypeEntity;
use App\GaelO\Interfaces\Repositories\VisitTypeRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use Exception;

class GetStudyVisitTypes
{

    private AuthorizationStudyService $authorizationStudyService;
    private VisitTypeRepositoryInterface $visitTypeRepositoryInterface;

    public function __construct(VisitTypeRepositoryInterface $visitTypeRepositoryInterface, AuthorizationStudyService $authorizationStudyService)
    {
        $this->visitTypeRepositoryInterface = $visitTypeRepositoryInterface;
        $this->authorizationStudyService = $authorizationStudyService;
    }

    public function execute(GetStudyVisitTypesRequest $getStudyVisitTypesRequest, GetStudyVisitTypesResponse $getStudyVisitTypesResponse): void
    {

        try {
            $currentUserId = $getStudyVisitTypesRequest->currentUserId;
            $studyName = $getStudyVisitTypesRequest->studyName;

            $this->checkAuthorization($currentUserId, $studyName);

            $visitTypes = $this->visitTypeRepositoryInterface->getVisitTypesOfStudy($studyName);

            $studyDetailResponse = [];

            foreach ($visitTypes as $visitType) {
                $visitTypeEntity = VisitTypeEntity::fillFromDBReponseArray($visitType);
                $visitTypeEntity->setVisitGroupContext($visitType['visit_group']);
                $studyDetailResponse[] = $visitTypeEntity;
            }

            $getStudyVisitTypesResponse->body = $studyDetailResponse;
            $getStudyVisitTypesResponse->status = 200;
            $getStudyVisitTypesResponse->statusText = 'OK';
        } catch (GaelOException $e) {
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
        };
    }
}
