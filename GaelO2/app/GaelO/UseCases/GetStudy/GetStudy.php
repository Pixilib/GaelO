<?php

namespace App\GaelO\UseCases\GetStudy;

use App\GaelO\Entities\StudyEntity;
use App\GaelO\Entities\VisitGroupEntity;
use App\GaelO\Entities\VisitTypeEntity;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class GetStudy {

    private AuthorizationUserService $authorizationUserService;
    private StudyRepositoryInterface $studyRepositoryInterface;

    public function __construct( AuthorizationUserService $authorizationUserService, StudyRepositoryInterface $studyRepositoryInterface )
    {
        $this->authorizationUserService = $authorizationUserService;
        $this->studyRepositoryInterface = $studyRepositoryInterface;
    }

    public function execute(GetStudyRequest $getStudyRequest, GetStudyResponse $getStudyResponse) {

        try {

            $curentUserId = $getStudyRequest->currentUserId;
            $studyName = $getStudyRequest->studyName;

            $this->checkAuthorization($curentUserId);

            $studyDetail = $this->studyRepositoryInterface->getstudyWithDetails($studyName);

            $studyDetailResponse = [];

            $studyEntity = StudyEntity::fillFromDBReponseArray($studyDetail);

            $visitGroupEntities = [];

            foreach ($studyDetail['visit_groups'] as $visitGroup) {
                $visitGroupEntity = VisitGroupEntity::fillFromDBReponseArray($visitGroup);

                $visitTypeEntities = [];
                foreach ($visitGroup['visit_types'] as $visitType) {
                    $visitTypeEntities[] = VisitTypeEntity::fillFromDBReponseArray($visitType);
                }
                $visitGroupEntity->setVisitTypes($visitTypeEntities);
                $visitGroupEntities[] = $visitGroupEntity;
            }

            $studyEntity->setVisitGroups($visitGroupEntities);

            $studyDetailResponse = $studyEntity;

            $getStudyResponse->body = $studyDetailResponse;
            $getStudyResponse->status = 200;
            $getStudyResponse->statusText = 'OK';

        } catch (AbstractGaelOException $e) {
            $getStudyResponse->body = $e->getErrorBody();
            $getStudyResponse->status = $e->statusCode;
            $getStudyResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }

    }

    private function checkAuthorization(int $userId): void
    {
        $this->authorizationUserService->setUserId($userId);
        if (!$this->authorizationUserService->isAdmin()) {
            throw new GaelOForbiddenException();
        }
    }
}
