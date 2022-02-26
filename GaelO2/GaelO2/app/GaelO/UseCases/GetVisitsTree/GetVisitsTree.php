<?php

namespace App\GaelO\UseCases\GetVisitsTree;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use App\GaelO\Services\TreeService\AbstractTreeService;
use Exception;

class GetVisitsTree {

    private AuthorizationStudyService $authorizationStudyService;
    private FrameworkInterface $frameworkInterface;
    private AbstractTreeService $abstractTreeService;

    public function __construct( AuthorizationStudyService $authorizationStudyService, FrameworkInterface $frameworkInterface )
    {
        $this->authorizationStudyService = $authorizationStudyService;
        $this->frameworkInterface = $frameworkInterface;
    }

    public function execute(GetVisitsTreeRequest $getVisitsTreeRequest, GetVisitsTreeResponse $getVisitsTreeResponse){

        try{

            $role = $getVisitsTreeRequest->role;

            if(!in_array($role, [Constants::ROLE_INVESTIGATOR, Constants::ROLE_MONITOR, Constants::ROLE_CONTROLLER, Constants::ROLE_REVIEWER])){
                throw new GaelOBadRequestException('Unavailable Role');
            }

            $this->checkAuthorization($getVisitsTreeRequest->currentUserId, $getVisitsTreeRequest->studyName, $role);

            $this->abstractTreeService = $this->frameworkInterface->make('\App\GaelO\Services\TreeService\\' . $role . 'TreeService');
            $this->abstractTreeService->setUserAndStudy($getVisitsTreeRequest->currentUserId, $role, $getVisitsTreeRequest->studyName);
            $tree = $this->abstractTreeService->buildTree();

            $getVisitsTreeResponse->body = $tree;
            $getVisitsTreeResponse->status = 200;
            $getVisitsTreeResponse->statusText = 'OK';

        } catch( GaelOException $e){

            $getVisitsTreeResponse->body = $e->getErrorBody();
            $getVisitsTreeResponse->status = $e->statusCode;
            $getVisitsTreeResponse->statusText = $e->statusText;

        } catch (Exception $e){

            throw $e;

        }
    }

    private function checkAuthorization(int $userId, string $studyName, string $role){
        $this->authorizationStudyService->setStudyName($studyName);
        $this->authorizationStudyService->setUserId($userId);
        if ( ! $this->authorizationStudyService->isAllowedStudy($role)){
            throw new GaelOForbiddenException();
        }
    }

}
