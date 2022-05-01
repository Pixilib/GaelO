<?php

namespace App\GaelO\UseCases\GetStudyStatistics;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class GetStudyStatistics {

    private AuthorizationUserService $authorizationUserService;
    private StudyRepositoryInterface $studyRepositoryInterface;

    public function __construct(AuthorizationUserService $authorizationUserService, StudyRepositoryInterface $studyRepositoryInterface)
    {
        $this->authorizationUserService = $authorizationUserService;
        $this->studyRepositoryInterface = $studyRepositoryInterface;

    }

    public function execute(GetStudyStatisticsRequest $getStudyStatisticsRequest, GetStudyStatisticsResponse $getStudyStatisticsResponse){

        try{
            $this->checkAuthorization($getStudyStatisticsRequest->currentUserId);

            $studyStatistics = $this->studyRepositoryInterface->getStudyStatistics($getStudyStatisticsRequest->studyName);

            $getStudyStatisticsResponse->body = $studyStatistics;
            $getStudyStatisticsResponse->status = 200;
            $getStudyStatisticsResponse->statusText = 'OK';

        } catch (GaelOException $e ){

            $getStudyStatisticsResponse->body = $e->getErrorBody();
            $getStudyStatisticsResponse->status = $e->statusCode;
            $getStudyStatisticsResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
        }

    }

    //Allowed if user is administrator
    private function checkAuthorization(int $userId){

        $this->authorizationUserService->setUserId($userId);
        if ( ! $this->authorizationUserService->isAdmin()) {
            throw new GaelOForbiddenException();
        }

    }
}
