<?php

namespace App\GaelO\Services\AuthorizationService;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Util;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
class AuthorizationWsiService {

    private string $originalStudyName;
    private string $level;
    private int $userId;
    private array $seriesEntity;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private PatientRepositoryInterface $patientRepositoryInterface;
    private StudyRepositoryInterface $studyRepositoryInterface;
    private UserRepositoryInterface $userRepositoryInterface;
    
    public function setUserId(int $userId)
    {
        $this->userId = $userId;
    }
    public function setRequestedUri(string $requestedURI): void
    {
        $url = parse_url($requestedURI);
        $this->setLevel($url);

        //Determine parent Visit ID depending of requested UID
        if ($this->level === "patients") {
            //If patient Level retrieve original study name from patient entity
            $patientId = $this->getPatientID($url);
            $patientEntity = $this->patientRepositoryInterface->find($patientId);
            $this->originalStudyName = $patientEntity['study_name'];
        } else {

            if ($this->level === "studies") {
                $requestedStudyInstanceUID = $this->getStudyInstanceUID($url);
                $studyEntity = $this->dicomStudyRepositoryInterface->getDicomStudy($requestedStudyInstanceUID, false);
                $visitId = $studyEntity['visit_id'];
            } else if ($this->level === "series") {
                $requestedSeriesInstanceUID = $this->getSeriesInstanceUID($url);
                $this->seriesEntity = $this->dicomSeriesRepositoryInterface->getSeries($requestedSeriesInstanceUID, false);
                $visitId = $this->seriesEntity['dicom_study']['visit_id'];
            }

            $visitContext = $this->visitRepositoryInterface->getVisitContext($visitId);
            $this->originalStudyName = $visitContext['patient']['study_name'];
        }
    }

    private function setLevel(array $url): void
    {

        if (key_exists('query',  $url)) {
            $params = [];
            parse_str($url['query'], $params);

            if (key_exists('00100020',  $params)) {
                $this->level = "patients";
                return;
            }

            if (key_exists('0020000D',  $params) || key_exists('StudyInstanceUID',  $params)) {
                $this->level = "studies";
                return;
            }
        }

        if (Util::endsWith($url['path'], "/studies"))  $this->level = "patients";
        else if (Util::endsWith($url['path'], "/series"))  $this->level = "studies";
        else $this->level = "series";
    }

    private function getPatientID(array $url): string
    {

        $params = [];
        parse_str($url['query'], $params);
        // Filter wild card beacause OHIF add wildcard
        if (key_exists('00100020',  $params)) return str_replace("*", "", $params['00100020']);
        else{
            throw new GaelOException("No Patient ID Found");
        }
    }


}