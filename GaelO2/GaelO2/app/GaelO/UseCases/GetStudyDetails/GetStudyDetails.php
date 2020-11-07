<?php

namespace App\GaelO\UseCases\GetStudyDetails;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\UseCases\GetStudy\StudyEntity;
use App\GaelO\UseCases\GetVisitGroup\VisitGroupEntity;
use App\GaelO\UseCases\GetVisitType\VisitTypeEntity;
use Exception;
use Study_Details;

class GetStudyDetails {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
    }

    public function execute(GetStudyDetailsRequest $getStudyDetailsRequest, GetStudyDetailsResponse $getStudyDetailsResponse) : void {

        try{

            $studyDetails = $this->persistenceInterface->getStudiesDetails($getStudyDetailsRequest->currentUserId);

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

    private function checkAuthorization(int $userId)  {
        $this->authorizationService->setCurrentUser($userId);
        if( ! $this->authorizationService->isAdmin($userId)) {
            throw new GaelOForbiddenException();
        };
    }
}
