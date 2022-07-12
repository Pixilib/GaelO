<?php

namespace App\GaelO\Services\AuthorizationService;

use App\GaelO\Adapters\FrameworkAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Entities\StudyEntity;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
class AuthorizationStudyService
{
    private string $studyName;
    private StudyEntity $studyEntity;
    private StudyRepositoryInterface $studyRepositoryInterface;
    private AuthorizationUserService $authorizationUserService;

    public function __construct(StudyRepositoryInterface $studyRepositoryInterface, AuthorizationUserService $authorizationUserService ) {
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->authorizationUserService = $authorizationUserService;
    }

    public function setStudyName(string $studyName) : void
    {
        $this->studyName = $studyName;
        $this->studyEntity = $this->studyRepositoryInterface->find($this->studyName);
    }

    public function setUserId(int $userId) : void {
        $this->authorizationUserService->setUserId($userId);
    }

    public function getStudyEntity(): StudyEntity
    {
        return $this->studyEntity;
    }

    public function isAllowedStudy(string $requestedRole) : bool {

        if ( $this->studyEntity->isAncillaryStudy() ) {
            //For Ancillaries studies only Reviewer and Supervisor role are allowed
            if( ! in_array($requestedRole, array(Constants::ROLE_REVIEWER, Constants::ROLE_SUPERVISOR))) return false;
        }

        //For all other cases access granted if role exists in the patient's study
        return $this->authorizationUserService->isRoleAllowed($requestedRole, $this->studyName);

    }

    public function getAuthorizationUserService() : AuthorizationUserService {
        return $this->authorizationUserService;
    }

    public static function isOrginalOrAncillaryStudyOf($requestedStudy, $originalStudyName){
        $studyEntity = FrameworkAdapter::make(StudyRepositoryInterface::class)->find($requestedStudy);
        return ( ($originalStudyName === $requestedStudy) || $studyEntity->isAncillaryStudyOf($originalStudyName));
    }

}
