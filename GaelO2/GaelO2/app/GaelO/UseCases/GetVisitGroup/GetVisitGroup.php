<?php

namespace App\GaelO\UseCases\GetVisitGroup;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Repositories\VisitGroupRepository;
use App\GaelO\Services\AuthorizationService;
use Exception;

class GetVisitGroup {

    private VisitGroupRepository $visitGroupRepository;
    private AuthorizationService $authorizationService;

    public function __construct(VisitGroupRepository $visitGroupRepository, AuthorizationService $authorizationService){
        $this->visitGroupRepository = $visitGroupRepository;
        $this->authorizationService = $authorizationService;
    }

    public function execute(GetVisitGroupRequest $getVisitGroupRequest, GetVisitGroupResponse $getVisitTypeResponse){

        try{
            $this->checkAuthorization($getVisitGroupRequest->currentUserId);
            $visitGroupData = $this->visitGroupRepository->find($getVisitGroupRequest->visitGroupId);
            $visitGroupEntity = VisitGroupEntity::fillFromDBReponseArray($visitGroupData);
            $getVisitTypeResponse->body = $visitGroupEntity;
            $getVisitTypeResponse->status = 200;
            $getVisitTypeResponse->statusText = 'OK';

        } catch (GaelOException $e){

            $getVisitTypeResponse->body = $e->getErrorBody();
            $getVisitTypeResponse->status = $e->statusCode;
            $getVisitTypeResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
        }


    }

    private function checkAuthorization(int $userId){
        $this->authorizationService->setCurrentUserAndRole($userId);
        if( ! $this->authorizationService->isAdmin() ) {
            throw new GaelOForbiddenException();
        }

    }
}
