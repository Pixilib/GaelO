<?php

/**
 Copyright (C) 2018 KANOUN Salim
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
//SK Quid du generator

class Export_Study_Data
{

    public $studyObject;
    private $allcreatedVisits;

    public function __construct(Study $studyObject)
    {

        $this->studyObject = $studyObject;

        $this->allcreatedVisits = [];

        $visitGroupArray = $this->studyObject->getAllPossibleVisitGroups();

        foreach ($visitGroupArray as $visitGroup) {

            try {
                $modalityCreatedVisit = $this->studyObject->getStudySpecificGroupManager($visitGroup->groupModality)->getCreatedVisits();
                array_push($this->allcreatedVisits, ...$modalityCreatedVisit);
            } catch (Exception $e) {
                error_log($e->getMessage());
            }
        }
    }

    /**
     * Generate and fill the patients CSV
     */
    public function exportPatientTable(): String
    {

        $patientCsv[] = array('Patient Code', 'Initials', 'Gender', 'Birthdate', 'Registration Date', 'Investigator Name', 'Center Code', 'Center Name', 'Country', 'Withdraw', 'Withdraw Reason', 'Withdraw Date');

        $patientsInStudy = $this->studyObject->getAllPatientsInStudy();
        foreach ($patientsInStudy as $patient) {
            $patientCenter = $patient->getPatientCenter();
            $patientCsv[] = array(
                $patient->patientCode, $patient->patientLastName . $patient->patientFirstName, $patient->patientGender,
                $patient->patientBirthDate, $patient->patientRegistrationDate, $patient->patientInvestigatorName, $patientCenter->code, $patientCenter->name, $patientCenter->countryName,
                $patient->patientWithdraw, $patient->patientWithdrawReason, $patient->patientWithdrawDateString
            );
        }

        $patientCsvString = $this->writeCsv($patientCsv);

        return $patientCsvString;
    }

    public function exportVisitTable(): String
    {

        $visitCSV = [];

        //Prepare visit CSV
        $visitCSV[] = array(
            'Patient Code', 'Visit Group', 'ID Visit', 'Code Status', 'Creator Name', 'Creator Date',
            'Type', 'Status', 'Reason For Not Done', 'Acquisition Date', 'Upload Status', 'Uploader',
            'Upload Date', 'State Investigator Form', 'State QC', 'QC done by', 'QC date', 'Review Status', 'Review Date', 'Review Conclusion', 'visit deleted'
        );

        foreach ($this->allcreatedVisits as $visit) {
            $codeStatus = $this->dertermineVisitStatusCode($visit);
            $visitCSV[] = array(
                $visit->patientCode, $visit->visitGroupObject->groupModality, $visit->id_visit, $codeStatus, $visit->creatorName, $visit->creationDate,
                $visit->visitType, $visit->statusDone, $visit->reasonForNotDone, $visit->acquisitionDate, $visit->uploadStatus, $visit->uploaderUsername,
                $visit->uploadDate, $visit->stateInvestigatorForm, $visit->stateQualityControl, $visit->controllerUsername, $visit->controlDate,
                $visit->reviewStatus, $visit->reviewConclusionDate, $visit->reviewConclusion, $visit->deleted
            );
        }

        $visitCsvString = $this->writeCsv($visitCSV);

        return $visitCsvString;
    }

    //A GENERALISER QUAND VIENDRA CYTOMINE
    public function getImagingData()
    {

        //Prepare Orthanc Series data CSV
        $orthancCSV[] = array(
            'ID Visit', 'Study Orthanc ID',
            'Study UID', 'Study Description', 'Dicom Patient Name', 'Dicom Patient ID', 'Serie Description', 'modality', 'Acquisition Date Time',
            'Serie Orthanc ID', 'Serie UID', 'Instance Number', 'Manufacturer', 'Disk Size', 'Serie Number', 'Patient Weight', 'Injected_Activity', 'Injected_Dose', 'Radiopharmaceutical', 'Half Life', 'Injected Time', 'Deleted'
        );

        $imagingVisit = array_filter($this->allcreatedVisits, function (Visit $visitObject) {
            $inArrayBool = in_array(
                $visitObject->visitGroupObject->groupModality,
                array(Visit_Group::GROUP_MODALITY_CT, Visit_Group::GROUP_MODALITY_PET, Visit_Group::GROUP_MODALITY_MR)
            );
            return ($inArrayBool);
        });

        foreach ($imagingVisit as $visit) {

            $allSeries = $visit->getSeriesDetails();

            foreach ($allSeries as $serieObject) {
                $studyDetailsObject = $serieObject->studyDetailsObject;
                $orthancCSV[] = array(
                    $studyDetailsObject->idVisit, $studyDetailsObject->studyOrthancId, $studyDetailsObject->studyUID,
                    $studyDetailsObject->studyDescription, $studyDetailsObject->patientName, $studyDetailsObject->patientId, $serieObject->seriesDescription, $serieObject->modality, $serieObject->acquisitionDateTime, $serieObject->seriesOrthancID,
                    $serieObject->serieUID, $serieObject->numberInstances, $serieObject->manufacturer, $serieObject->serieUncompressedDiskSize, $serieObject->seriesNumber, $serieObject->patientWeight, $serieObject->injectedActivity, $serieObject->injectedDose, $serieObject->radiopharmaceutical, $serieObject->halfLife, $serieObject->injectedDateTime, $serieObject->deleted
                );
            }
        }

        $orthancCsvFile = $this->writeCsv($orthancCSV);

        return $orthancCsvFile;
    }

    public function getReviewData()
    {

        $mappedVisitByGroup = [];

        foreach ($this->allcreatedVisits as $visitObject) {
            $modality = $visitObject->visitGroupObject->groupModality;
            $visitName = $visitObject->visitType;
            $mappedVisitByGroup[$modality][$visitName][] = $visitObject;
        };

        foreach ($mappedVisitByGroup as $modality => $visitTypes) {

            $groupObject = $this->studyObject->getSpecificGroup($modality);

            foreach ($visitTypes as $visitType => $visitArray) {
                $csv = [];

                //Export Reviews
                $genericHeader = array('ID Visit', 'ID review', 'Reviewer', 'Review Date', 'Validated', 'Local Form', 'Adjudcation_form', 'Review Deleted');

                $visitTypeObject = $groupObject->getVisitType($visitType);
                $specificFormTable = $visitTypeObject->getSpecificFormColumn();
                unset($specificFormTable[0]);

                $csv[] = array_merge($genericHeader, $specificFormTable);

                foreach ($visitArray as $visitObject) {

                    array_push($csv, ...$this->getReviews($visitObject) );

                }

                $reviewCsvFiles[$modality . '_' . $visitType] = $this->writeCsv($csv);
            }
        }

        return $reviewCsvFiles;
    }

    private function getReviews(Visit $visitObject) : Array
    {

        $localReviews = [];
        try {
            $localReviews[] = $visitObject->getReviewsObject(true);
        } catch (Exception $e) {
            error_log($e->getMessage());
        }

        $expertReviews = [];
        try {
            $expertReviews = $visitObject->getReviewsObject(false);
        } catch (Exception $e) {
            error_log($e->getMessage());
        }

        //Merge all reviews in an array
        $reviewObjects = [];

        if (!empty($localReviews)) {
            array_push($reviewObjects, ...$localReviews);
        }

        if (!empty($expertReviews)) {
            array_push($reviewObjects, ...$expertReviews);
        }

        $csv = [];
        foreach ($reviewObjects as $reviewObject) {
            $csv[] = $this->getReviewDatas($reviewObject);
        }

        return $csv;
    }

    private function getReviewDatas(Review $review) : Array
    {
        //Add to final map
        $reviewDatas = $this->getGenericData($review);
        $specificData = $review->getSpecificData();
        unset($specificData["id_review"]);

        $reviewLine = array_merge($reviewDatas, array_values($specificData) );

        error_log(implode(',',$reviewLine) );

        return $reviewLine;
    }


    private function getGenericData(Review $review)
    {
        //Add to final map
        $reviewDatas = array(
            $review->id_visit, $review->id_review,
            $review->username, $review->reviewDate, $review->validated, $review->isLocal, $review->isAdjudication, $review->deleted
        );
        error_log(implode(',', $reviewDatas));
        return $reviewDatas;
    }

    private function writeCsv($csvArray)
    {

        $tempCsv = tempnam(ini_get('upload_tmp_dir'), 'TMPCSV_');
        $fichier_csv = fopen($tempCsv, 'w');
        foreach ($csvArray as $fields) {
            fputcsv($fichier_csv, $fields);
        }
        fclose($fichier_csv);

        return $tempCsv;
    }


    /**
     * Return Code Status
     * 0 Visit not Done
     * 1 Done but DICOM and Form not sent
     * 2 Done but upload not done (form sent)
     * 3 done but investigator form not done (dicom sent)
     * 4 QC not done
     * 5 QC corrective action
     * 6 QC refused
     * 7 Review Not Done
     * 8 Review ongoing
     * 9 Review Wait adjudication
     * 10 review done
     * @param Visit $visitObject
     * @return number
     */
    private function dertermineVisitStatusCode(Visit $visitObject)
    {

        if ($visitObject->statusDone == Visit::NOT_DONE) {
            return 0;
        } else if ($visitObject->uploadStatus == Visit::NOT_DONE || $visitObject->stateInvestigatorForm == Visit::NOT_DONE) {
            if ($visitObject->uploadStatus == Visit::NOT_DONE && $visitObject->stateInvestigatorForm == Visit::NOT_DONE) {
                return 1;
            } else if ($visitObject->stateInvestigatorForm == Visit::NOT_DONE) {
                return 3;
            } else if ($visitObject->uploadStatus == Visit::NOT_DONE) {
                return 2;
            }
        } else if ($visitObject->qcStatus == Visit::QC_NOT_DONE) {
            return 4;
        } else if ($visitObject->qcStatus == Visit::QC_CORRECTIVE_ACTION_ASKED) {
            return 5;
        } else if ($visitObject->qcStatus == Visit::QC_REFUSED) {
            return 6;
        } else if ($visitObject->reviewStatus == Visit::NOT_DONE) {
            return 7;
        } else if ($visitObject->reviewStatus == Form_Processor::ONGOING) {
            return 8;
        } else if ($visitObject->reviewStatus == Form_Processor::WAIT_ADJUDICATION) {
            return 9;
        } else if ($visitObject->reviewStatus == Form_Processor::DONE) {
            return 10;
        }
    }
}
