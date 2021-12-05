<?php

namespace App\GaelO\Services\AuthorizationService;

use App\GaelO\Interfaces\Repositories\DicomSeriesRepositoryInterface;
use App\GaelO\Interfaces\Repositories\DicomStudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Util;

class AuthorizationDicomWebService {

    private string $originalStudyName;
    private string $level;
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
        $this->setLevel($url);

        //Determine parent Visit ID depending of requested UID
        if($this->level === "studies"){

            $requestedStudyInstanceUID = $this->getStudyInstanceUID($url);
            $studyEntity = $this->dicomStudyRepositoryInterface->getDicomStudy($requestedStudyInstanceUID, false);
            $visitId = $studyEntity['visit_id'];

        }else if ($this->level === "series"){

            $requestedSeriesInstanceUID = $this->getSeriesInstanceUID($url);
            $this->seriesEntity = $this->dicomSeriesRepositoryInterface->getSeries($requestedSeriesInstanceUID, false);
            $visitId = $this->seriesEntity['dicom_study']['visit_id'];

        }

        $visitContext = $this->visitRepositoryInterface->getVisitContext($visitId);
        $this->originalStudyName = $visitContext['patient']['study_name'];

    }

    private function getStudyInstanceUID(array $url) : string {
        if( key_exists('query',  $url) ){
            $params = [];
            parse_str($url['query'], $params);
            if(key_exists('0020000D',  $params)) return $params['0020000D'];
        }
        return $this->getUID($url['path'], "studies");
    }

    private function getSeriesInstanceUID(array $url)  : string {
        return $this->getUID($url['path'], "series");
    }


    private function setLevel(array $url){

        if( key_exists('query',  $url) ){
            $params = [];
            parse_str($url['query'], $params);
            if(key_exists('0020000D',  $params)) {
                $this->level = "studies";
                return;
            };
        }

        if (Util::endsWith($url['path'], "/series"))  $this->level = "studies";
        else $this->level = "series";

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


    /**
     * As URI are defined by DicomWeb syntax we can't know what is the study scope
     * So we are cheking that the requested dicom are linked to a primary or ancillary study
     * in which the user has a role
     */
    //SK ICI A AMELIORER EN FAISANT QUE LE VIEWER ENVOI L ETUDE COURANTE
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
