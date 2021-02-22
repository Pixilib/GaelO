<?php

namespace App\GaelO\UseCases\GetVisitsFromStudy;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\ReviewStatusRepositoryInterface;
use App\GaelO\Interfaces\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\UseCases\GetVisit\VisitEntity;
use Exception;
class GetVisitsFromStudy {

    private VisitRepositoryInterface $visitRepositoryInterface;
    private ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface;
    private AuthorizationService $authorizationService;

    public function __construct(VisitRepositoryInterface $visitRepositoryInterface, ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface, AuthorizationService $authorizationService){
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->authorizationService = $authorizationService;
        $this->reviewStatusRepositoryInterface = $reviewStatusRepositoryInterface;
    }

    public function execute(GetVisitsFromStudyRequest $getVisitsFromStudyRequest, GetVisitsFromStudyResponse $getVisitsFromStudyResponse){

        try{

            $studyName = $getVisitsFromStudyRequest->studyName;

            $this->checkAuthorization($getVisitsFromStudyRequest->currentUserId, $getVisitsFromStudyRequest->studyName);

            $dbData = $this->visitRepositoryInterface->getVisitsInStudy($studyName, true);
            $responseArray = [];
            foreach($dbData as $data){
                $responseEntity = VisitEntity::fillFromDBReponseArray($data);
                $responseEntity->setReviewVisitStatus($data['review_status']['review_status'], $data['review_status']['review_conclusion_value'], $data['review_status']['review_conclusion_date']);
                $responseArray[] = $responseEntity;
            }

            $getVisitsFromStudyResponse->body = $responseArray;
            $getVisitsFromStudyResponse->status = 200;
            $getVisitsFromStudyResponse->statusText = 'OK';

        } catch( GaelOException $e){

            $getVisitsFromStudyResponse->body = $e->getErrorBody();
            $getVisitsFromStudyResponse->status  = $e->statusCode;
            $getVisitsFromStudyResponse->statusText = $e->statusText;

        } catch (Exception $e){

            throw $e;

        }

    }

    private function checkAuthorization(int $userId, String $studyName){
        $this->authorizationService->setCurrentUserAndRole($userId, Constants::ROLE_SUPERVISOR);
        if ( ! $this->authorizationService->isRoleAllowed($studyName)){
            throw new GaelOForbiddenException();
        }
    }
}
