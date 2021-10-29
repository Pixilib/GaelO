<?php

namespace App\GaelO\UseCases\GetStudyDetailsSupervisor;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Entities\VisitGroupEntity;
use App\GaelO\Entities\VisitTypeEntity;
use Exception;

class GetStudyDetailsSupervisor {

    private StudyRepositoryInterface $studyRepositoryInterface;
    private AuthorizationService $authorizationService;

    public function __construct(StudyRepositoryInterface $studyRepositoryInterface, AuthorizationService $authorizationService){
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(GetStudyDetailsSupervisorRequest $getStudyDetailsSupervisorRequest, GetStudyDetailsSupervisorResponse $etStudyDetailsSupervisorResponse) : void {

        try{
            $this->checkAuthorization($getStudyDetailsSupervisorRequest->currentUserId, $getStudyDetailsSupervisorRequest->studyName);

            $studyDetails = $this->studyRepositoryInterface->getStudyDetails($getStudyDetailsSupervisorRequest->studyName);

            $studyDetailResponse = [];

            foreach($studyDetails['visit_group_details'] as $visitGroupDetails){
                foreach($visitGroupDetails['visit_types'] as $visitType){
                    $visitTypeEntity = VisitTypeEntity::fillFromDBReponseArray($visitType);
                    $visitTypeEntity->setVisitGroupContext($visitGroupDetails);
                    $studyDetailResponse[] = $visitTypeEntity;
                }
            }

            $etStudyDetailsSupervisorResponse->body = $studyDetailResponse;
            $etStudyDetailsSupervisorResponse->status = 200;
            $etStudyDetailsSupervisorResponse->statusText = 'OK';

        } catch (GaelOException $e ){

            $etStudyDetailsSupervisorResponse->body = $e->getErrorBody();
            $etStudyDetailsSupervisorResponse->status = $e->statusCode;
            $etStudyDetailsSupervisorResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
        }


    }

    private function checkAuthorization(int $userId, string $studyName) : void {
        $this->authorizationService->setCurrentUserAndRole($userId, Constants::ROLE_SUPERVISOR);
        if( ! $this->authorizationService->isRoleAllowed($studyName) ) {
            throw new GaelOForbiddenException();
        };
    }
}
