<?php

namespace App\GaelO\UseCases\GetStudyDetails;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Entities\StudyEntity;
use App\GaelO\Entities\VisitGroupEntity;
use App\GaelO\Entities\VisitTypeEntity;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class GetStudyDetails
{

    private StudyRepositoryInterface $studyRepositoryInterface;
    private AuthorizationUserService $authorizationUserService;

    public function __construct(StudyRepositoryInterface $studyRepositoryInterface, AuthorizationUserService $authorizationUserService)
    {
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->authorizationUserService = $authorizationUserService;
    }

    public function execute(GetStudyDetailsRequest $getStudyDetailsRequest, GetStudyDetailsResponse $getStudyDetailsResponse): void
    {

        try {
            $this->checkAuthorization($getStudyDetailsRequest->currentUserId);

            $studyDetails = $this->studyRepositoryInterface->getAllStudiesWithDetails($getStudyDetailsRequest->currentUserId);

            $studyDetailResponse = [];

            //Loop study details to construct details nested array
            foreach ($studyDetails as $studyDetail) {
                $studyEntity = StudyEntity::fillFromDBReponseArray($studyDetail);
                $studyName = $studyEntity->name;
                $studyDetailResponse[$studyName] = get_object_vars($studyEntity);
                $studyDetailResponse[$studyName]['visitGroups'] = [];

                foreach ($studyDetail['visit_group_details'] as $visitGroupDetails) {
                    $visitGroupEntity = VisitGroupEntity::fillFromDBReponseArray($visitGroupDetails);

                    $studyDetailResponse[$studyName]['visitGroups'][$visitGroupEntity->id] = get_object_vars($visitGroupEntity);
                    $studyDetailResponse[$studyName]['visitGroups'][$visitGroupEntity->id]['visitTypes']= [];

                    foreach ($visitGroupDetails['visit_types'] as $visitType) {
                        $visitTypeEntity = VisitTypeEntity::fillFromDBReponseArray($visitType);
                        $studyDetailResponse[$studyName]['visitGroups'][$visitGroupEntity->id]['visitTypes'][$visitTypeEntity->id] = get_object_vars($visitTypeEntity);
                    }
                }
            }

            $getStudyDetailsResponse->body = $studyDetailResponse;
            $getStudyDetailsResponse->status = 200;
            $getStudyDetailsResponse->statusText = 'OK';

        } catch (GaelOException $e) {

            $getStudyDetailsResponse->body = $e->getErrorBody();
            $getStudyDetailsResponse->status = $e->statusCode;
            $getStudyDetailsResponse->statusText = $e->statusText;

        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $userId): void
    {
        $this->authorizationUserService->setUserId($userId);
        if (!$this->authorizationUserService->isAdmin()) {
            throw new GaelOForbiddenException();
        };
    }
}
