<?php

namespace App\GaelO\UseCases\GetStudies;

use App\GaelO\Entities\StudyEntity;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class GetStudies
{

    private StudyRepositoryInterface $studyRepositoryInterface;
    private AuthorizationUserService $authorizationUserService;

    public function __construct(StudyRepositoryInterface $studyRepositoryInterface, AuthorizationUserService $authorizationUserService)
    {
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->authorizationUserService = $authorizationUserService;
    }

    public function execute(GetStudiesRequest $getStudiesRequest, GetStudiesResponse $getStudiesResponse): void
    {

        try {
            $this->checkAuthorization($getStudiesRequest->currentUserId);

            $studies = $this->studyRepositoryInterface->getStudies($getStudiesRequest->withTrashed);

            $responseArray = [];
            foreach ($studies as $study) {
                $responseArray[] = StudyEntity::fillFromDBReponseArray($study);
            }

            $getStudiesResponse->body = $responseArray;
            $getStudiesResponse->status = 200;
            $getStudiesResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $getStudiesResponse->body = $e->getErrorBody();
            $getStudiesResponse->status = $e->statusCode;
            $getStudiesResponse->statusText = $e->statusText;
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
