<?php

namespace App\GaelO\UseCases\GetStudyDetails;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\UseCases\GetStudy\StudyEntity;
use App\GaelO\UseCases\GetVisitGroup\VisitGroupEntity;
use App\GaelO\UseCases\GetVisitType\VisitTypeEntity;
use Exception;

class GetStudyDetails {

    private StudyRepositoryInterface $studyRepositoryInterface;
    private AuthorizationService $authorizationService;

    public function __construct(StudyRepositoryInterface $studyRepositoryInterface, AuthorizationService $authorizationService){
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(GetStudyDetailsRequest $getStudyDetailsRequest, GetStudyDetailsResponse $getStudyDetailsResponse) : void {

        try{
            $this->checkAuthorization($getStudyDetailsRequest->currentUserId);

            $studyDetails = $this->studyRepositoryInterface->getAllStudiesWithDetails($getStudyDetailsRequest->currentUserId);

            $studyDetailResponse = [];

            //Loop study details to construct details nested array
            foreach($studyDetails as $studyDetail){
                $studyEntity = StudyEntity::fillFromDBReponseArray($studyDetail);
                $studyName =$studyEntity->name;
                $studyDetailResponse[$studyName] =  get_object_vars($studyEntity);

                foreach($studyDetail['visit_group_details'] as $visitGroupDetails){
                    $visitGroupEntity = VisitGroupEntity::fillFromDBReponseArray($visitGroupDetails);

                    $studyDetailResponse[$studyName][$visitGroupEntity->id] = get_object_vars($visitGroupEntity);

                    foreach($visitGroupDetails['visit_types'] as $visitType){
                        $visitTypeEntity = VisitTypeEntity::fillFromDBReponseArray($visitType);
                        $studyDetailResponse[$studyName][$visitGroupEntity->id]['visitTypes'][$visitTypeEntity->id] = get_object_vars($visitTypeEntity);
                    }

                }
            }

            $getStudyDetailsResponse->body = $studyDetailResponse;
            $getStudyDetailsResponse->status = 200;
            $getStudyDetailsResponse->statusText = 'OK';

        } catch (GaelOException $e ){

            $getStudyDetailsResponse->body = $e->getErrorBody();
            $getStudyDetailsResponse->status = $e->statusCode;
            $getStudyDetailsResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
        }


    }

    private function checkAuthorization(int $userId) : void {
        $this->authorizationService->setCurrentUserAndRole($userId);
        if( ! $this->authorizationService->isAdmin() ) {
            throw new GaelOForbiddenException();
        };
    }
}
