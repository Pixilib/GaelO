<?php

namespace App\GaelO\Services;

use App\GaelO\Adapters\SpreadsheetAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\PatientRepositoryInterface;
use App\GaelO\Interfaces\StudyRepositoryInterface;
use App\GaelO\Interfaces\VisitRepositoryInterface;



//SK TO BE EXPORTED with deleted rows
//VisitTable (1 spreedsheet by visitType  => Reste A ajouter VisitStatus du Lysarc)
//DicomTable (Studies and Series) => SK A FAIRE Un fichier avec 2 sheet
//ReviewTable (local and Review separated) => un fichier avec 2 sheet
//Associated file to review => SK TODO

class ExportDataService {
    private PatientRepositoryInterface $patientRepositoryInterface;
    private StudyRepositoryInterface $studyRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;

    private String $studyName;

    public function __construct(
        PatientRepositoryInterface $patientRepositoryInterface,
        StudyRepositoryInterface $studyRepositoryInterface,
        VisitRepositoryInterface $visitRepositoryInterface)
    {
        $this->patientRepositoryInterface = $patientRepositoryInterface;
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
    }

    public function setStudyName(string $studyName){
        $this->studyName = $studyName;
    }

    public function exportPatientTable(){
        $patientData = $this->patientRepositoryInterface->getPatientsInStudy($this->studyName);
        $spreadsheetAdapter = new SpreadsheetAdapter();
        $spreadsheetAdapter->setDefaultWorksheetTitle('Patients');
        $spreadsheetAdapter->fillData('Patients', $patientData);
        $tempFileName = $this->createTempFile();
        $spreadsheetAdapter->writeToExcel($tempFileName);
        return $tempFileName;
    }

    public function exportVisitTable(){
        //List all visitType
        $studyDetails = $this->studyRepositoryInterface->getStudyDetails($this->studyName);

        $spreadsheetAdapter = new SpreadsheetAdapter();
        $spreadsheetAdapter->setDefaultWorksheetTitle('dummy');

        //Loop each visitType and export data for each one
        foreach ( $studyDetails['visit_group_details'] as $visitGroup) {

            foreach($visitGroup['visit_types'] as $visitType){
                //Determine Sheet Name
                $sheetName = $visitGroup['modality'].'_'.$visitType['name'];
                $spreadsheetAdapter->addSheet($sheetName);
                $visitsData = $this->visitRepositoryInterface->getVisitsInVisitType($visitType['id'], true, $this->studyName);
                //Flatten the nested review status
                $flattenedData = array_map(function($visitData){
                    $reviewStatus = $visitData['review_status'];
                    unset($visitData['review_status']);
                    unset($visitData['updated_at']);
                    //SK RESTE A AJOUTER VISIT STATUS=> A sortir dans un post processing lysarc ?
                    return array_merge($visitData, $reviewStatus);
                }, $visitsData);

                $spreadsheetAdapter->fillData($sheetName, $flattenedData);
            }
        }
        //Export created file
        $tempFileName = $this->createTempFile();
        $spreadsheetAdapter->writeToExcel($tempFileName);
        return $tempFileName;
    }

    private function createTempFile(){
        $tempFile = tmpfile();
        $tempFileMetadata = stream_get_meta_data($tempFile);
        return $tempFileMetadata["uri"];
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
	 * -1 If any of these case (should not happen)
	 * @param Visit $visitObject
	 * @return number
	 */
    /*
	private function dertermineVisitStatusCode(array $visitEntity) : int
	{

		if ($visitObject->statusDone == Constants::VISIT_STATUS_NOT_DONE) {
			return 0;
		}
        else if ($visitObject->uploadStatus ==Constants::UPLOAD_STATUS_NOT_DONE || PROCESSING || $visitObject->stateInvestigatorForm == Visit::NOT_DONE) {
			if ($visitObject->uploadStatus == Visit::NOT_DONE && $visitObject->stateInvestigatorForm == Visit::NOT_DONE) {
				return 1;
			}else if ($visitObject->stateInvestigatorForm == Visit::NOT_DONE) {
				return 3;
			}else if ($visitObject->uploadStatus == Visit::NOT_DONE) {
				return 2;
			}
		}else if ($visitObject->qcStatus == Visit::QC_NOT_DONE) {
			return 4;
		}else if ($visitObject->qcStatus == Visit::QC_CORRECTIVE_ACTION_ASKED) {
			return 5;
		}else if ($visitObject->qcStatus == Visit::QC_REFUSED) {
			return 6;
		}else if ($visitObject->reviewStatus == Visit::NOT_DONE) {
			return 7;
		}else if ($visitObject->reviewStatus == Visit::REVIEW_ONGOING) {
			return 8;
		}else if ($visitObject->reviewStatus == Visit::REVIEW_WAIT_ADJUDICATION) {
			return 9;
		}else if ($visitObject->reviewStatus == Visit::REVIEW_DONE) {
			return 10;
		}else {
			//If none of these case return -1, should not happen
			return -1;
		}
	}
    */

}
