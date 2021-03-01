<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\DicomSeriesRepositoryInterface;
use App\GaelO\Interfaces\DicomStudyRepositoryInterface;
use App\GaelO\Interfaces\UserRepositoryInterface;
use App\GaelO\Interfaces\VisitRepositoryInterface;
use App\GaelO\Util;

class AuthorizationDicomWebService
{

    private DicomStudyRepositoryInterface $dicomStudyRepositoryInterface;
    private DicomSeriesRepositoryInterface $dicomSeriesRepositoryInterface;
    private UserRepositoryInterface $userRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private AuthorizationVisitService $authorizationVisitService;
    private array $availableRoles;
    private int $userId;

    public function __construct(
        DicomStudyRepositoryInterface $dicomStudyRepositoryInterface,
        DicomSeriesRepositoryInterface $dicomSeriesRepositoryInterface,
        UserRepositoryInterface $userRepositoryInterface,
        VisitRepositoryInterface $visitRepositoryInterface,
        AuthorizationVisitService $authorizationVisitService
    ) {

        $this->dicomStudyRepositoryInterface = $dicomStudyRepositoryInterface;
        $this->dicomSeriesRepositoryInterface = $dicomSeriesRepositoryInterface;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->authorizationVisitService = $authorizationVisitService;
    }

    public function setUserIdAndRequestedUri(int $userId, string $requestedURI): void
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

        $this->visitContext = $this->visitRepositoryInterface->getVisitContext($visitId);
        $studyName = $this->visitContext['visit_type']['visit_group']['study_name'];
        $this->availableRoles = $this->userRepositoryInterface->getUsersRolesInStudy($userId, $studyName);
        $this->visitId = $visitId;
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
        if (!in_array(Constants::ROLE_SUPERVISOR, $availableRoles) && $this->level == "series" && $this->seriesEntity['deleted_at'] != null) {
            return false;
        }

        foreach ($availableRoles as $role) {

            if (in_array($role, [Constants::ROLE_SUPERVISOR, Constants::ROLE_CONTROLLER, Constants::ROLE_INVESTIGATOR])) {
                $this->authorizationVisitService->setCurrentUserAndRole($this->userId, $role);
                $this->authorizationVisitService->setVisitId($this->visitId);
                if ($this->authorizationVisitService->isVisitAllowed()) {
                    return true;
                };
            };

            if ($role === Constants::ROLE_REVIEWER) {
                //Une des visites du patients doit etre en attente de review
                return true;
            }
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

        if ($endStudyUIDPosition) {
            $studyUID = substr($studySubString, 0, $endStudyUIDPosition);
        } else {
            $studyUID = $studySubString;
        };

        return $studyUID;
    }
}
