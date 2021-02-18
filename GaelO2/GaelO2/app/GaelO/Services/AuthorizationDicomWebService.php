<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\OrthancSeriesRepositoryInterface;
use App\GaelO\Interfaces\OrthancStudyRepositoryInterface;
use App\GaelO\Interfaces\UserRepositoryInterface;
use App\GaelO\Interfaces\VisitRepositoryInterface;
use App\GaelO\Util;

class AuthorizationDicomWebService
{

    private OrthancStudyRepositoryInterface $orthancStudyRepository;
    private OrthancSeriesRepositoryInterface $orthancSeriesRepository;
    private UserRepositoryInterface $userRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private AuthorizationVisitService $authorizationVisitService;
    private array $availableRoles;
    private int $userId;

    public function __construct(
        OrthancStudyRepositoryInterface $orthancStudyRepositoryInterface,
        OrthancSeriesRepositoryInterface $orthancSeriesRepositoryInterface,
        UserRepositoryInterface $userRepositoryInterface,
        VisitRepositoryInterface $visitRepositoryInterface,
        AuthorizationVisitService $authorizationVisitService)
    {

        $this->orthancStudyRepository=$orthancStudyRepositoryInterface;
        $this->orthancSeriesRepository=$orthancSeriesRepositoryInterface;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->userRepositoryInterface=$userRepositoryInterface;
        $this->authorizationVisitService = $authorizationVisitService;

    }

    public function setUserIdAndRequestedUri(int $userId, string $requestedURI): void
    {

        if (Util::endsWith($requestedURI, "/series"))  $this->level = "studies";
        else $this->level = "series";

        //Extract StudyInstanceUID from requested URI
        $requestedInstanceUID = $this->getUID($requestedURI, $this->level);

        if ($this->level === "series") {
            $this->seriesEntity = $this->orthancSeriesRepository->getSeriesBySeriesInstanceUID($requestedInstanceUID, true);
            $visitId = $this->seriesEntity['orthanc_study']['visit_id'];
        } else if ($this->level === "studies") {
            $studyEntity = $this->orthancStudyRepository->getOrthancStudyByStudyInstanceUID($requestedInstanceUID, true);
            $visitId = $studyEntity['visit_id'];
        }

        $this->visitContext = $this->visitRepositoryInterface->getVisitContext($visitId);
        $studyName = $this->visitContext['visit_type']['visit_group']['study_name'];
        $this->availableRoles = $this->userRepositoryInterface->getUsersRolesInStudy($userId, $studyName);
        $this->authorizationVisitService->setVisitId($visitId);
        $this->userId = $userId;
    }

    /**
     * Check that visit is granter for the calling user (still awaiting review or still awaiting QC)
     * @param string $id_visit
     * @return boolean
     */
    public function isDicomAllowed(): bool
    {

        $candidatesRoles  = [Constants::ROLE_SUPERVISOR, Constants::ROLE_CONTROLLER, Constants::ROLE_REVIEWER,  Constants::ROLE_INVESTIGATOR];

        $availableRoles = array_intersect($this->availableRoles, $candidatesRoles);

        //If series requested and has been softdeleted, refuse access except supervisor is in available roles
        if( !in_array(Constants::ROLE_SUPERVISOR, $availableRoles) && $this->level == "series" && $this->seriesEntity['deleted_at'] != null ){
            return false;
        }

        foreach($availableRoles as $role){

            //SK ON REDUPLIQUE BEACOUP DE REQUETTE SQL A REFLECHIR SI FAUT PAS DISSOCIER DE AUTHORIZATION VISIT SERVICE
            $this->authorizationVisitService->setCurrentUserAndRole($this->userId, $role);
            if( $this->authorizationVisitService->isVisitAllowed() ){
                return true;
            };

        }

        return false;
    }

    /**
     * Isolate the called Study or Series Instance UID
     * @return string
     */
    private function getUID(string $requestedURI, string $level): string
    {

        $studySubString = strstr($requestedURI, "/" . $level . "/");
        $studySubString = str_replace("/" . $level . "/", "", $studySubString);

        $endStudyUIDPosition = strpos($studySubString, "/");

        if($endStudyUIDPosition){
            $studyUID = substr($studySubString, 0, $endStudyUIDPosition);
        }else{
            $studyUID = $studySubString;
        };

        return $studyUID;
    }



}
