<?php

namespace App\GaelO\UseCases\GetStudy;

use App\GaelO\Entities\StudyEntity;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class GetStudy{

    private StudyRepositoryInterface $studyRepositoryInterface;
    private AuthorizationUserService $authorizationUserService;

    public function __construct(StudyRepositoryInterface $studyRepositoryInterface, AuthorizationUserService $authorizationUserService){
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->authorizationUserService = $authorizationUserService;

    }

    public function execute(GetStudyRequest $getStudyRequest, GetStudyResponse $getStudyResponse) : void{

        try{
            $this->checkAuthorization($getStudyRequest->currentUserId);

            $studies = $this->studyRepositoryInterface->getStudies(true);

            $responseArray = [];
            foreach($studies as $study){
                $responseArray[] = StudyEntity::fillFromDBReponseArray($study);
            }

            $getStudyResponse->body = $responseArray;
            $getStudyResponse->status = 200;
            $getStudyResponse->statusText = 'OK';

        } catch (GaelOException $e){

            $getStudyResponse->body = $e->getErrorBody();
            $getStudyResponse->status = $e->statusCode;
            $getStudyResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
        }


    }

    private function checkAuthorization(int $userId) : void {
        $this->authorizationUserService->setUserId($userId);
        if( ! $this->authorizationUserService->isAdmin()) {
            throw new GaelOForbiddenException();
        };
    }

}
