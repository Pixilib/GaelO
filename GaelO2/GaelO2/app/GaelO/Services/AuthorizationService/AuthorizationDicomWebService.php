<?php

namespace App\GaelO\Services\AuthorizationService;

use App\GaelO\Constants\Constants;
use App\GaelO\Util;

class AuthorizationDicomWebService {

    private int $userId;
    private AuthorizationVisitService $authorizationVisitService;

    public function __construct(AuthorizationVisitService $authorizationVisitService)
    {
        $this->authorizationVisitService = $authorizationVisitService;
    }

    public function setUserId(int $userId){
        $this->userId = $userId;

    }

    public function setRequestedUri(string $requestedURI): void
    {

        if (Util::endsWith($requestedURI, "/series"))  $this->level = "studies";
        else $this->level = "series";

        //Extract StudyInstanceUID from requested URI
        $requestedInstanceUID = $this->getUID($requestedURI, $this->level);

        if ($this->level === "series") {
            $this->seriesEntity = $this->dicomSeriesRepositoryInterface->getSeries($requestedInstanceUID, true);
            $visitId = $this->seriesEntity['dicom_study']['visit_id'];
        } else if ($this->level === "studies") {
            $studyEntity = $this->dicomStudyRepositoryInterface->getDicomStudy($requestedInstanceUID, true);
            $visitId = $studyEntity['visit_id'];
        }

        $this->visitId = $visitId;

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

        if ($endStudyUIDPosition) {
            $studyUID = substr($studySubString, 0, $endStudyUIDPosition);
        } else {
            $studyUID = $studySubString;
        };

        return $studyUID;
    }

    public function isDicomAllowed(string $role): bool
    {

        $candidatesRoles  = [Constants::ROLE_SUPERVISOR, Constants::ROLE_CONTROLLER, Constants::ROLE_REVIEWER,  Constants::ROLE_INVESTIGATOR];

        //SK Ici charger les role de l'utilisateur dans l'étude (en passant par authorization user service)
        $availableRoles = array_intersect($this->availableRoles, $candidatesRoles);

        //If series requested and has been softdeleted, refuse access except supervisor is in available roles
        if (!in_array(Constants::ROLE_SUPERVISOR, $availableRoles) && $this->level == "series" && $this->seriesEntity['deleted_at'] != null) {
            return false;
        }

        foreach ($availableRoles as $role) {
            $this->authorizationVisitService->setUserId($this->userId);
            $this->authorizationVisitService->setVisitId($this->visitId);
            if ($this->authorizationVisitService->isVisitAllowed($role)) {
                return true;
            };
        }

        return false;
    }

}
