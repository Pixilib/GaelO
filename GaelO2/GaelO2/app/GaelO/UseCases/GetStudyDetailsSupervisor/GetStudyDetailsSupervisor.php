<?php

namespace App\GaelO\UseCases\GetStudyDetailsSupervisor;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Entities\VisitTypeEntity;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use Exception;

class GetStudyDetailsSupervisor {

    private StudyRepositoryInterface $studyRepositoryInterface;
    private AuthorizationStudyService $authorizationStudyService;

    public function __construct(StudyRepositoryInterface $studyRepositoryInterface, AuthorizationStudyService $authorizationStudyService){
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->authorizationStudyService = $authorizationStudyService;
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
        $this->authorizationStudyService->setUserId($userId);
        $this->authorizationStudyService->setStudyName($studyName);
        if( ! $this->authorizationStudyService->isAllowedStudy(Constants::ROLE_SUPERVISOR) ) {
            throw new GaelOForbiddenException();
        };
    }
}
