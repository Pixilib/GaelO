<?php

namespace App\GaelO\UseCases\GetVisit;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Services\AuthorizationVisitService;
use App\GaelO\Services\VisitService;
use Exception;

class GetVisit {

    private VisitService $visitService;
    private AuthorizationVisitService $authorizationVisitService;

    public function __construct(VisitService $visitService, AuthorizationVisitService $authorizationVisitService){
        $this->visitService = $visitService;
        $this->authorizationVisitService = $authorizationVisitService;
    }

    public function execute(GetVisitRequest $getVisitRequest, GetVisitResponse $getVisitResponse){

        try{

            $visitId = $getVisitRequest->visitId;
            $this->checkAuthorization($visitId, $getVisitRequest->currentUserId, $getVisitRequest->role);

            $dbData = $this->visitService->getVisitData($visitId);
            $reviewStatus = $this->visitService->getReviewStatus($visitId, $getVisitRequest->studyName);

            $responseEntity = VisitEntity::fillFromDBReponseArray($dbData);
            $responseEntity->setReviewVisitStatus($reviewStatus['review_status'], $reviewStatus['review_conclusion_value'] ,$reviewStatus['review_conclusion_date']);

            $getVisitResponse->body = $responseEntity;

            $getVisitResponse->status = 200;
            $getVisitResponse->statusText = 'OK';

        } catch( GaelOException $e){

            $getVisitResponse->body = $e->getErrorBody();
            $getVisitResponse->status  = $e->statusCode;
            $getVisitResponse->statusText = $e->statusText;

        } catch (Exception $e){

            throw $e;

        }

    }

    private function checkAuthorization(int $visitId, int $userId, string $role){
        $this->authorizationVisitService->setCurrentUserAndRole($userId, $role);
        $this->authorizationVisitService->setVisitId($visitId);
        if( ! $this->authorizationVisitService->isVisitAllowed()){
            throw new GaelOForbiddenException();
        }
    }
}
