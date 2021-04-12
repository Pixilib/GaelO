<?php

namespace App\GaelO\UseCases\GetVisit;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\ReviewStatusRepositoryInterface;
use App\GaelO\Interfaces\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationVisitService;
use Exception;

class GetVisit {

    private VisitRepositoryInterface $visitRepositoryInterface;
    private ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface;
    private AuthorizationVisitService $authorizationVisitService;

    public function __construct(VisitRepositoryInterface $visitRepositoryInterface, ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface, AuthorizationVisitService $authorizationVisitService){
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->authorizationVisitService = $authorizationVisitService;
        $this->reviewStatusRepositoryInterface = $reviewStatusRepositoryInterface;
    }

    public function execute(GetVisitRequest $getVisitRequest, GetVisitResponse $getVisitResponse){

        try{

            $visitId = $getVisitRequest->visitId;
            $this->checkAuthorization($visitId, $getVisitRequest->currentUserId, $getVisitRequest->role);

            $visitEntity = $this->visitRepositoryInterface->getVisitContext($visitId);
            $reviewStatus = $this->reviewStatusRepositoryInterface->getReviewStatus($visitId, $getVisitRequest->studyName);

            $responseEntity = VisitEntity::fillFromDBReponseArray($visitEntity);
            $responseEntity->setVisitContext(
                $visitEntity['visit_type']['visit_group']['modality'],
                $visitEntity['visit_type']['name'],
                $visitEntity['visit_type']['order'],
                $visitEntity['visit_type']['optional'],
                $visitEntity['visit_type']['visit_group']['id']
            );
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
