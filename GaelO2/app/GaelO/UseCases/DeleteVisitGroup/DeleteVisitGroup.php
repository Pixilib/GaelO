<?php

namespace App\GaelO\UseCases\DeleteVisitGroup;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\VisitGroupRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class DeleteVisitGroup {

    private VisitGroupRepositoryInterface $visitGroupRepositoryInterface;
    private AuthorizationUserService $authorizationUserService;

    public function __construct(VisitGroupRepositoryInterface $visitGroupRepositoryInterface, AuthorizationUserService $authorizationUserService){
        $this->visitGroupRepositoryInterface = $visitGroupRepositoryInterface;
        $this->authorizationUserService = $authorizationUserService;
    }

    public function execute(DeleteVisitGroupRequest $deleteVisitGroupRequest, DeleteVisitGroupResponse $deleteVisitGroupResponse){

        try{

            $currentUserId = $deleteVisitGroupRequest->currentUserId;
            $visitGroupId = $deleteVisitGroupRequest->visitGroupId;

            $this->checkAuthorization($currentUserId);

            $hasVisitTypes = $this->visitGroupRepositoryInterface->hasVisitTypes($visitGroupId);

            if($hasVisitTypes) throw new GaelOForbiddenException('Existing Child Visit Type');

            $this->visitGroupRepositoryInterface->delete($visitGroupId);

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
        $this->authorizationUserService->setUserId($userId);
        if( ! $this->authorizationUserService->isAdmin()) {
            throw new GaelOForbiddenException();
        }
    }
}
