<?php

namespace App\GaelO\Services\AuthorizationService;

use App\GaelO\Interfaces\Repositories\DicomSeriesRepositoryInterface;
use App\GaelO\Interfaces\Repositories\DicomStudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Util;
use Illuminate\Support\Facades\Log;

class AuthorizationDicomWebService {

    private string $originalStudyName;
    private DicomSeriesRepositoryInterface $dicomSeriesRepositoryInterface;
    private DicomStudyRepositoryInterface $dicomStudyRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private StudyRepositoryInterface $studyRepositoryInterface;
    private UserRepositoryInterface $userRepositoryInterface;

    public function __construct(
        DicomSeriesRepositoryInterface $dicomSeriesRepositoryInterface,
        DicomStudyRepositoryInterface $dicomStudyRepositoryInterface,
        VisitRepositoryInterface $visitRepositoryInterface,
        StudyRepositoryInterface $studyRepositoryInterface,
        UserRepositoryInterface $userRepositoryInterface
        )
    {
        $this->dicomStudyRepositoryInterface = $dicomStudyRepositoryInterface;
        $this->dicomSeriesRepositoryInterface = $dicomSeriesRepositoryInterface;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->userRepositoryInterface = $userRepositoryInterface;
    }

    public function setUserId(int $userId){
        $this->userId = $userId;

    }

    public function setRequestedUri(string $requestedURI): void
    {
        $url = parse_url($requestedURI);
        if($this->isStoneOfOrthanc($url)){
            Log::info($url['path']);
            if (Util::endsWith($url['path'], "/series"))  $this->level = "studies";
            else $this->level = "series";
            Log::info($this->level);
            $queryParams = [];
            parse_str($url['query'], $queryParams);
            $requestedInstanceUID = $queryParams['0020000D'];
            Log::info($queryParams);

        }else{
            if (Util::endsWith($requestedURI, "/series"))  $this->level = "studies";
            else $this->level = "series";
            //Extract StudyInstanceUID from requested URI
            $requestedInstanceUID = $this->getUIDOHIF($requestedURI, $this->level);
        }

        if ($this->level === "series") {
            $this->seriesEntity = $this->dicomSeriesRepositoryInterface->getSeries($requestedInstanceUID, false);
            $visitId = $this->seriesEntity['dicom_study']['visit_id'];
        } else if ($this->level === "studies") {
            $studyEntity = $this->dicomStudyRepositoryInterface->getDicomStudy($requestedInstanceUID, false);
            $visitId = $studyEntity['visit_id'];
        }

        $visitContext = $this->visitRepositoryInterface->getVisitContext($visitId);
        $this->originalStudyName = $visitContext['patient']['study_name'];

    }

    private function isStoneOfOrthanc(array $url) : bool {
        return str_contains('0020000D=',  $url['query']) ;
    }

    /**
     * Isolate the called Study or Series Instance UID
     * @return string
     */
    private function getUIDOHIF(string $requestedURI, string $level): string
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

    /**
     * As URI are defined by DicomWeb syntax we can't know what is the study scope
     * So we are cheking that the requested dicom are linked to a primary or ancillary study
     * in which the user has a role
     */
    public function isDicomAllowed(): bool
    {

        //Get Ancilaries study of the original studyName
        $studies = $this->studyRepositoryInterface->getAncillariesStudyOfStudy($this->originalStudyName);
        $studies[] = $this->originalStudyName;

        //Get User's Role
        $availableRoles = $this->userRepositoryInterface->getUsersRoles($this->userId);
        $userStudies = array_keys($availableRoles);

        return sizeOf( array_intersect($studies, $userStudies) ) > 0 ;
    }

}
