<?php

/**
 Copyright (C) 2018-2020 KANOUN Salim
 This program is free software; you can redistribute it and/or modify
 it under the terms of the Affero GNU General Public v.3 License as published by
 the Free Software Foundation;
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 Affero GNU General Public Public for more details.
 You should have received a copy of the Affero GNU General Public Public along
 with this program; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
 */

namespace App\GaelO\Services;

use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Repositories\OrthancSeriesRepository;
use App\GaelO\Repositories\OrthancStudyRepository;
use App\GaelO\Services\OrthancService;
use App\GaelO\Services\StoreObjects\OrthancSeries;
use App\GaelO\Services\StoreObjects\OrthancStudy;
use App\GaelO\Util;
use DateTime;
use Exception;

/**
 * Fill Orthanc_Studies and Orthanc_Series table to link DICOM Orthanc storage with the web plateform
 * Used in the validation process of the upload
 */

class RegisterOrthancStudyService
{

    private OrthancService $orthancService;
    private int $visitId;
    private int $userId;
    private string $originalStudyOrthancId;
    private $studyOrthancObject;


    public function __construct(OrthancService $orthancService, VisitService $visitService, OrthancStudyRepository $orthancStudyRepository, OrthancSeriesRepository $orthancSeriesRepository)
    {
        $this->orthancService = $orthancService;
        $this->visitService = $visitService;
        $this->orthancStudyRepository = $orthancStudyRepository;
        $this->orthancSeriesRepository = $orthancSeriesRepository;
    }

    public function setData(int $visitId, string $userId, string $studyOrthancId, string $originalStudyOrthancId)
    {
        $this->visitId = $visitId;
        $this->userId = $userId;
        $this->studyOrthancId = $studyOrthancId;
        $this->originalStudyOrthancId = $originalStudyOrthancId;
    }

    /**
     * return study details of a study stored in Orthanc
     * @param String $studyID
     * @return array
     */
    public function registerOrthancStudy()
    {
        $studyData = new OrthancStudy($this->orthancService, $this->studyOrthancId);
        $studyData->retrieveStudyData();
        $this->studyOrthancObject = $studyData;
        $this->fillDB();
    }

    /**
     * Fill data in the database, write data for study and for each series
     * Once done trigger the change upload status of visit to update upload status and eventually skip
     * local form and/or QC
     */
    public function fillDB()
    {

        //Check that original OrthancID is unknown for this study
        if ($this->orthancStudyRepository->isExistingOriginalOrthancStudyID($this->originalStudyOrthancId)) {
            try {
                //Fill la database
                $this->addToDbStudy();

                foreach ($this->studyOrthancObject->orthancSeries as $serie) {
                    //Fill series database
                    $this->addtoDbSerie($serie);
                }
                $this->visitService->updateUploadStatus($this->visitId, Constants::UPLOAD_STATUS_DONE, $this->username);
            } catch (Exception $e1) {
                throw new Exception("Error during import " . $e1->getMessage());
            }
        } else {
            throw new GaelOBadRequestException("Error during import Study Already Known");
        }
    }

    /**
     * Private function to write into Orthanc_Studies DB
     * @param string $anonFromOrthancStudyId
     */
    private function addToDbStudy()
    {
        $studyAcquisitionDate = $this->parseDateTime($this->studyOrthancObject->studyDate, 1);
        $studyAcquisitionTime = $this->parseDateTime($this->studyOrthancObject->studyTime, 2);

        if ($this->orthancStudyRepository->isExistingOrthancStudyID($this->studyOrthancId)) {

            $this->orthancStudyRepository->updateStudy(
                $this->studyOrthancId,
                $this->visitId,
                $this->userId,
                Util::now(),
                $studyAcquisitionDate,
                $studyAcquisitionTime,
                $this->originalStudyOrthancId,
                $this->studyOrthancObject->studyInstanceUID,
                $this->studyOrthancObject->studyDescription,
                $this->studyOrthancObject->parentPartientOrthancID,
                $this->studyOrthancObject->parentPatientName,
                $this->studyOrthancObject->parentPatientID,
                $this->studyOrthancObject->numberOfSeriesInStudy,
                $this->studyOrthancObject->countInstances,
                $this->studyOrthancObject->diskSizeMb,
                $this->studyOrthancObject->uncompressedSizeMb
            );
        } else {

            $this->orthancStudyRepository->addStudy(
                $this->studyOrthancId,
                $this->visitId,
                $this->userId,
                Util::now(),
                $studyAcquisitionDate,
                $studyAcquisitionTime,
                $this->originalStudyOrthancId,
                $this->studyOrthancObject->studyInstanceUID,
                $this->studyOrthancObject->studyDescription,
                $this->studyOrthancObject->parentPartientOrthancID,
                $this->studyOrthancObject->parentPatientName,
                $this->studyOrthancObject->parentPatientID,
                $this->studyOrthancObject->numberOfSeriesInStudy,
                $this->studyOrthancObject->countInstances,
                $this->studyOrthancObject->diskSizeMb,
                $this->studyOrthancObject->uncompressedSizeMb
            );
        }
    }

    /**
     * Private function to write into the Orthanc_Series DB
     * @param Orthanc_Serie $serie
     */
    private function addtoDbSerie(OrthancSeries $series)
    {

        $serieAcquisitionDate = $this->parseDateTime($series->seriesDate, 1);
        $serieAcquisitionTime = $this->parseDateTime($series->seriesTime, 2);
        $injectedDateTime =$this->parseDateTime($series->injectedDateTime, 0);

        if ($this->orthancSeriesRepository->isExistingOrthancSeriesID($series->serieOrthancID)) {

            $this->orthancSeriesRepository->updateSeries(
                $series->serieOrthancID,
                $series->parentStudyOrthancID,
                $serieAcquisitionDate,
                $serieAcquisitionTime,
                $series->modality,
                $series->seriesDescription,
                $series->injectedDose,
                $series->radiopharmaceutical,
                $series->halfLife,
                $injectedDateTime,
                $series->injectedActivity,
                $series->patientWeight,
                $series->numberOfInstanceInOrthanc,
                $series->seriesInstanceUID,
                $series->seriesNumber,
                $series->diskSizeMb,
                $series->uncompressedSizeMb,
                $series->manufacturer,
                $series->modelName
            );
        } else {
            $this->orthancSeriesRepository->addSeries(
                $series->serieOrthancID,
                $series->parentStudyOrthancID,
                $serieAcquisitionDate,
                $serieAcquisitionTime,
                $series->modality,
                $series->seriesDescription,
                $series->injectedDose,
                $series->radiopharmaceutical,
                $series->halfLife,
                $injectedDateTime,
                $series->injectedActivity,
                $series->patientWeight,
                $series->numberOfInstanceInOrthanc,
                $series->seriesInstanceUID,
                $series->seriesNumber,
                $series->diskSizeMb,
                $series->uncompressedSizeMb,
                $series->manufacturer,
                $series->modelName
            );
        }

    }

    /**
     * Parse a DICOM date or Time string and return a string ready to send to database
     * Return null if non parsable
     * @param string $string
     * @param type 0=dateTime, 1=Date, 2=Time
     * return formated date for db saving, null if parse failed
     */
    private function parseDateTime(?string $string, int $type)
    {
        $parsedDateTime = null;

        //If contain time split the ms (after.) which are not constant
        if ($type == 0 || $type == 2) {
            if (strpos($string, ".")) {
                $timeWithoutms = explode(".", $string);
                $string = $timeWithoutms[0];
            }
        }

        if ($type == 2) {
            $dateObject = DateTime::createFromFormat('His', $string);
            if ($dateObject !== false) {
                $parsedDateTime = $dateObject->format('H:i:s');
            }
        } else if ($type == 1) {
            $dateObject = DateTime::createFromFormat('Ymd', $string);
            if ($dateObject !== false) {
                $parsedDateTime = $dateObject->format('Y-m-d');
            }
        } else if ($type == 0) {
            $dateObject = DateTime::createFromFormat('YmdHis', $string);
            if ($dateObject !== false) {
                $parsedDateTime = $dateObject->format('Y-m-d H:i:s');
            }
        }

        return $parsedDateTime;
    }
}
