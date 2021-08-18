<?php

namespace App\GaelO\UseCases\DeleteVisitGroup;

use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\VisitGroupRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;

class DeleteVisitGroup {

    private VisitGroupRepositoryInterface $visitGroupRepositoryInterface;
    private AuthorizationService $authorizationService;

    public function __construct(VisitGroupRepositoryInterface $visitGroupRepositoryInterface, AuthorizationService $authorizationService){
        $this->visitGroupRepositoryInterface = $visitGroupRepositoryInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(DeleteVisitGroupRequest $deleteVisitGroupRequest, DeleteVisitGroupResponse $deleteVisitGroupResponse){

        try{
            $this->checkAuthorization($deleteVisitGroupRequest->currentUserId);

            $hasVisitTypes = $this->visitGroupRepositoryInterface->hasVisitTypes($deleteVisitGroupRequest->visitGroupId);

            if($hasVisitTypes) throw new GaelOBadRequestException('Existing Child Visit Type');

            $this->visitGroupRepositoryInterface->delete($deleteVisitGroupRequest->visitGroupId);
            $deleteVisitGroupResponse->status = 200;
            $deleteVisitGroupResponse->statusText = 'OK';


        }catch (GaelOException $e){
            $deleteVisitGroupResponse->body = $e->getErrorBody();
            $deleteVisitGroupResponse->status = $e->statusCode;
            $deleteVisitGroupResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
        }


    }

    public function checkAuthorization(int $userId) : void {
        $this->authorizationService->setCurrentUserAndRole($userId);
        if( ! $this->authorizationService->isAdmin()) {
            throw new GaelOForbiddenException();
        };
    }
}
