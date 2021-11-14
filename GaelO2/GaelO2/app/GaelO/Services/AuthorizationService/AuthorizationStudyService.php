<?php

namespace App\GaelO\Services\AuthorizationService;

use App\GaelO\Adapters\FrameworkAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Repositories\StudyRepository;

class AuthorizationStudyService
{
    private int $studyName;
    private array $studyData;
    private StudyRepositoryInterface $studyRepositoryInterface;

    public function __construct(StudyRepositoryInterface $studyRepositoryInterface ) {
        $this->studyRepositoryInterface = $studyRepositoryInterface;
    }

    public function setStudyName(string $studyName)
    {
        $this->studyName = $studyName;
    }

    private function fillStudyData(){
        if($this->studyData == null) $this->studyData = $this->studyRepositoryInterface->find($this->studyName);
    }


    public function isAncillaryStudy(): bool
    {
        $this->fillStudyData();
        return $this->studyData['ancillary_of'] == null ? false : true;
    }

    public function isAncillaryStudyOf(String $studyName): bool
    {
        $this->fillStudyData();
        return $this->studyData['ancillary_of'] === $studyName ? true : false;
    }

    public function isAllowedStudy(int $userId, string $requestedRole, string $studyName) : bool {

        $authorizationUserService = FrameworkAdapter::make(AuthorizationUserService::class);
        $authorizationUserService->setUserId($userId);

        if ( $this->isAncillaryStudy() ) {
            //For Ancillaries studies only Reviewer and Supervisor role are allowed
            if( ! in_array($requestedRole, array(Constants::ROLE_REVIEWER, Constants::ROLE_SUPERVISOR))) return false;
        }

        //For all other cases access granted if role exists in the patient's study
        return $studyName === $this->studyName && $this->authorizationUserService->isRoleAllowed($requestedRole, $studyName);

    }



}
