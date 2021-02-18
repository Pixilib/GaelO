<?php

namespace App\GaelO\UseCases\GetVisitsFromStudy;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\ReviewStatusRepositoryInterface;
use App\GaelO\Interfaces\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationVisitService;
use App\GaelO\Services\AuthorizationService;
use Exception;
use Log;
class GetVisitsFromStudy {

    private VisitRepositoryInterface $visitRepositoryInterface;
    private ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface;
    private AuthorizationVisitService $authorizationVisitService;

    public function __construct(VisitRepositoryInterface $visitRepositoryInterface, ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface, AuthorizationService $authorizationService){
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->authorizationService = $authorizationService;
        $this->reviewStatusRepositoryInterface = $reviewStatusRepositoryInterface;
    }

    public function execute(GetVisitsFromStudyRequest $GetVisitsFromStudyRequest, GetVisitsFromStudyResponse $GetVisitsFromStudyResponse){

        try{

            $studyName = $GetVisitsFromStudyRequest->studyName;

            $this->checkAuthorization($GetVisitsFromStudyRequest->currentUserId, $GetVisitsFromStudyRequest->role, $GetVisitsFromStudyRequest->studyName);
            
            Log::info('ici');
            $dbData = $this->visitRepositoryInterface->GetVisitsInStudy($studyName);
            $responseArray = [];
            foreach($dbData as $data){
                Log::info($data);
                $visitId = $data['visit_type']['visit_group']['id'];
                $reviewStatus = $this->reviewStatusRepositoryInterface->getReviewStatus($visitId, $GetVisitsFromStudyRequest->studyName);

                $responseEntity = VisitEntity::fillFromDBReponseArray($dbData);
                $responseEntity->setReviewVisitStatus($reviewStatus['review_status'], $reviewStatus['review_conclusion_value'] ,$reviewStatus['review_conclusion_date']);
                $responseArray[] = $responseEntity;
            }

            $getUserResponse->body = $responseArray;    
            $dbData = $this->visitRepositoryInterface->find($visitId);
            $reviewStatus = $this->reviewStatusRepositoryInterface->getReviewStatus($visitId, $GetVisitsFromStudyRequest->studyName);

            $responseEntity = VisitEntity::fillFromDBReponseArray($dbData);
            $responseEntity->setReviewVisitStatus($reviewStatus['review_status'], $reviewStatus['review_conclusion_value'] ,$reviewStatus['review_conclusion_date']);

            $GetVisitsFromStudyResponse->body = $responseEntity;

            $GetVisitsFromStudyResponse->status = 200;
            $GetVisitsFromStudyResponse->statusText = 'OK';

        } catch( GaelOException $e){

            $GetVisitsFromStudyResponse->body = $e->getErrorBody();
            $GetVisitsFromStudyResponse->status  = $e->statusCode;
            $GetVisitsFromStudyResponse->statusText = $e->statusText;

        } catch (Exception $e){

            throw $e;

        }

    }

    private function checkAuthorization(int $userId, string $role, String $studyName){
        $this->authorizationService->setCurrentUserAndRole($userId, $role);
        if ( ! $this->authorizationService->isRoleAllowed($studyName)){
            throw new GaelOForbiddenException();
        }
    }
}
