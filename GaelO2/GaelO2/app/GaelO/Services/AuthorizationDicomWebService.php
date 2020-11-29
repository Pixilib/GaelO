<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\OrthancSeriesRepository;
use App\GaelO\Repositories\OrthancStudyRepository;
use App\GaelO\Util;

class AuthorizationDicomWebService
{

    private OrthancStudyRepository $orthancStudyRepository;
    private OrthancSeriesRepository $orthancSeriesRepository;
    private AuthorizationVisitService $authorizationVisitService;
    private string $requestedRole;

    public function __construct(
        OrthancStudyRepository $orthancStudyRepository,
        OrthancSeriesRepository $orthancSeriesRepository,
        AuthorizationVisitService $authorizationVisitService)
    {

        $this->orthancStudyRepository=$orthancStudyRepository;
        $this->$orthancSeriesRepository=$orthancSeriesRepository;
        $this->authorizationVisitService = $authorizationVisitService;

    }

    public function setCurrentUserAndRole(int $userId, string $role){
        $this->requestedRole = $role;
        $this->authorizationVisitService->setCurrentUserAndRole($userId, $role);
    }

    public function setRequestedUri(string $requestedURI): void
    {

        if (Util::endsWith($requestedURI, "/series"))  $level = "studies";
        else $level = "series";

        $includedDeleted = $this->requestedRole === Constants::ROLE_SUPERVISOR ? true : false;

        //Extract StudyInstanceUID from requested URI
        $requestedInstanceUID = $this->getUID($requestedURI, $level);

        if ($level === "series") {
            $seriesEntity = $this->orthancSeriesRepository->getSeriesBySeriesInstanceUID($requestedInstanceUID, $includedDeleted);
            $visitId = $seriesEntity['orthanc_study']['visit_id'];
        } else if ($level === "studies") {
            $studyEntity = $this->orthancStudyRepository->getStudyByStudyInstanceUID($requestedInstanceUID, $includedDeleted);
            $visitId = $studyEntity['visit_id'];
        }

        $this->authorizationVisitService->setVisitId($visitId);
    }



    /**
     * Check that visit is granter for the calling user (still awaiting review or still awaiting QC)
     * @param string $id_visit
     * @return boolean
     */
    public function isDicomAllowed(): bool
    {
        $uploadStatus = $this->authorizationVisitService->visitUploadStatus;

        //Check Visit Availability of the calling user
        if (($this->role == Constants::ROLE_INVESTIGATOR && $uploadStatus == Constants::UPLOAD_STATUS_DONE)) {
            $visitCheck = $this->authorizationVisitService->isVisitAllowed();
        } else if ($this->role == Constants::ROLE_REVIEWER && $this->visitReviewAvailable) {
            //SK RESTE A CHECKER QUE LE REVIEWER DOIT ENCORE FAIRE UNE REVIEW POUR CE PATIENT?
            //OU PLUTOT DOIT ETRE GERER DANS VISIT ALLOWED
            $visitCheck = $this->authorizationVisitService->isVisitAllowed();
        } else if ($this->role == Constants::ROLE_SUPERVISOR) {
            $visitCheck = $this->authorizationVisitService->isVisitAllowed();
        } else {
            //Other roles (monitor) can't have access to images
            $visitCheck = false;
        }

        return $visitCheck;
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
