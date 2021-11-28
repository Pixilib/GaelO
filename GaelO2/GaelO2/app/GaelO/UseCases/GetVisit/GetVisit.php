<?php

namespace App\GaelO\UseCases\GetVisit;

use App\GaelO\Entities\VisitEntity;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\ReviewStatusRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use Exception;

class GetVisit {

    private VisitRepositoryInterface $visitRepositoryInterface;
    private ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface;
    private AuthorizationVisitService $authorizationVisitService;
    private UserRepositoryInterface $userRepositoryInterface;

    public function __construct(VisitRepositoryInterface $visitRepositoryInterface, ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface, UserRepositoryInterface $userRepositoryInterface, AuthorizationVisitService $authorizationVisitService){
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->authorizationVisitService = $authorizationVisitService;
        $this->reviewStatusRepositoryInterface = $reviewStatusRepositoryInterface;
        $this->userRepositoryInterface =  $userRepositoryInterface;
    }

    public function execute(GetVisitRequest $getVisitRequest, GetVisitResponse $getVisitResponse){

        try{

            $visitId = $getVisitRequest->visitId;
            $this->checkAuthorization($visitId, $getVisitRequest->currentUserId, $getVisitRequest->role, $getVisitRequest->studyName);

            $visitEntity = $this->visitRepositoryInterface->getVisitContext($visitId);
            $reviewStatus = $this->reviewStatusRepositoryInterface->getReviewStatus($visitId, $getVisitRequest->studyName);
            $userEntity  = $this->userRepositoryInterface->find($visitEntity['creator_user_id']);

            $responseEntity = VisitEntity::fillFromDBReponseArray($visitEntity);
            $responseEntity->setVisitContext(
                $visitEntity['visit_type']['visit_group'],
                $visitEntity['visit_type']
            );
            $responseEntity->setReviewVisitStatus($reviewStatus['review_status'], $reviewStatus['review_conclusion_value'] ,$reviewStatus['review_conclusion_date'], $reviewStatus['target_lesions']);
            $responseEntity->setCreatorDetails($userEntity['firstname'], $userEntity['lastname']);

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

    private function checkAuthorization(int $visitId, int $userId, string $role, string $studyName){
        $this->authorizationVisitService->setUserId($userId);
        $this->authorizationVisitService->setStudyName($studyName);
        $this->authorizationVisitService->setVisitId($visitId);

        if( ! $this->authorizationVisitService->isVisitAllowed($role)){
            throw new GaelOForbiddenException();
        }
    }
}
