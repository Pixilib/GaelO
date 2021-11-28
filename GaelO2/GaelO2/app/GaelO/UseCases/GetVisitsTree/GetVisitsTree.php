<?php

namespace App\GaelO\UseCases\GetVisitsTree;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use App\GaelO\Services\VisitTreeService;
use Exception;

class GetVisitsTree {

    private AuthorizationStudyService $authorizationStudyService;
    private VisitTreeService $visitTreeService;

    public function __construct( AuthorizationStudyService $authorizationStudyService, VisitTreeService $visitTreeService )
    {
        $this->authorizationStudyService = $authorizationStudyService;
        $this->visitTreeService = $visitTreeService;
    }

    public function execute(GetVisitsTreeRequest $getVisitsTreeRequest, GetVisitsTreeResponse $getVisitsTreeResponse){

        try{

            $this->checkAuthorization($getVisitsTreeRequest->currentUserId, $getVisitsTreeRequest->studyName, $getVisitsTreeRequest->role);

            $this->visitTreeService->setUserAndStudy($getVisitsTreeRequest->currentUserId, $getVisitsTreeRequest->role, $getVisitsTreeRequest->studyName);
            $tree = $this->visitTreeService->buildTree();

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
