<?php

namespace App\GaelO\Services;

use App\GaelO\Adapters\SpreadsheetAdapter;
use App\GaelO\Interfaces\DicomSeriesRepositoryInterface;
use App\GaelO\Interfaces\DicomStudyRepositoryInterface;
use App\GaelO\Interfaces\PatientRepositoryInterface;
use App\GaelO\Interfaces\ReviewRepositoryInterface;
use App\GaelO\Interfaces\StudyRepositoryInterface;
use App\GaelO\Interfaces\VisitRepositoryInterface;

//SK
//VisitTable  => Reste A ajouter VisitStatus du Lysarc=> A faire a part das une couche d'abstraction car suivra par les evolution de la plateforme)
//Associated file to review => SK TODO dans un zip

//Dans Review => Ajouter PatientCode et VisitType
//Dans Visit => Ajouter en colonne VisitGroup et VisitType
//Fusionner dicom study / series en duplicant les colonne study ?
//Referencer les fichiers résultats dans cet object ?

class ExportDataService {
    private PatientRepositoryInterface $patientRepositoryInterface;
    private StudyRepositoryInterface $studyRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private DicomStudyRepositoryInterface $dicomStudyRepositoryInterface;
    private DicomSeriesRepositoryInterface $dicomSeriesRepositoryInterface;
    private ReviewRepositoryInterface $reviewRepositoryInterface;

    private string $studyName;

    public function __construct(
        PatientRepositoryInterface $patientRepositoryInterface,
        StudyRepositoryInterface $studyRepositoryInterface,
        VisitRepositoryInterface $visitRepositoryInterface,
        DicomStudyRepositoryInterface $dicomStudyRepositoryInterface,
        DicomSeriesRepositoryInterface $dicomSeriesRepositoryInterface,
        ReviewRepositoryInterface $reviewRepositoryInterface)
    {
        $this->patientRepositoryInterface = $patientRepositoryInterface;
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->dicomStudyRepositoryInterface = $dicomStudyRepositoryInterface;
        $this->dicomSeriesRepositoryInterface = $dicomSeriesRepositoryInterface;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
    }

    public function setStudyName(string $studyName){
        $this->studyName = $studyName;

        //List Visit Type of this study
        $studyDetails = $this->studyRepositoryInterface->getStudyDetails($this->studyName);

        $visitTypeArray = [];

        foreach ( $studyDetails['visit_group_details'] as $visitGroup) {
            foreach($visitGroup['visit_types'] as $visitType){
                $visitTypeArray[ $visitType['id'] ] = [
                    'modality'=>$visitGroup['modality'],
                    'name'=>$visitType['name']
                ];
            }
        }

        $this->visitTypeArray = $visitTypeArray;

        //Store Id of visits of this study
        $availableVisits = $this->visitRepositoryInterface->getVisitsInStudy($this->studyName, false, true);
        $this->visitIdArray = array_map(function($visit){
            return $visit['id'];
        }, $availableVisits);

    }

    public function exportPatientTable(){
        $patientData = $this->patientRepositoryInterface->getPatientsInStudy($this->studyName);
        $spreadsheetAdapter = new SpreadsheetAdapter();
        $spreadsheetAdapter->addSheet('Patients');
        $spreadsheetAdapter->fillData('Patients', $patientData);
        $tempFileName = $this->createTempFile();
        $spreadsheetAdapter->writeToExcel($tempFileName);
        return $tempFileName;
    }

    public function exportVisitTable(){

        $spreadsheetAdapter = new SpreadsheetAdapter();

        //Loop each visitType and export data for each one
        foreach($this->visitTypeArray as $id => $visitGroupDetails){
            //Determine Sheet Name
            $sheetName = $visitGroupDetails['modality'].'_'.$visitGroupDetails['name'];
            $visitsData = $this->visitRepositoryInterface->getVisitsInVisitType($id, true, $this->studyName, true);
            //Flatten the nested review status
            $flattenedData = array_map(function($visitData){
                $reviewStatus = $visitData['review_status'];
                unset($visitData['review_status']);
                return array_merge($visitData, $reviewStatus);
            }, $visitsData);
            $spreadsheetAdapter->addSheet($sheetName);
            $spreadsheetAdapter->fillData($sheetName, $flattenedData);
        }
        //Export created file
        $tempFileName = $this->createTempFile();
        $spreadsheetAdapter->writeToExcel($tempFileName);
        return $tempFileName;
    }

    public function exportDicomsTable(){

        $spreadsheetAdapter = new SpreadsheetAdapter();

        $dicomStudyData = $this->dicomStudyRepositoryInterface->getDicomStudyFromVisitIdArray($this->visitIdArray, true);
        $spreadsheetAdapter->addSheet('DicomStudies');
        $spreadsheetAdapter->fillData('DicomStudies', $dicomStudyData);

        $studyInstanceUIDArray = array_map(function ($studyEntity){
            return $studyEntity['study_uid'];
        }, $dicomStudyData);
        //Get Series data for series spreadsheet
        $dicomSeriesData = $this->dicomSeriesRepositoryInterface->getDicomSeriesOfStudyInstanceUIDArray($studyInstanceUIDArray, true);
        $spreadsheetAdapter->addSheet('DicomSeries');
        $spreadsheetAdapter->fillData('DicomSeries', $dicomSeriesData);

        //Export created file
        $tempFileName = $this->createTempFile();
        $spreadsheetAdapter->writeToExcel($tempFileName);
        return $tempFileName;

    }

    public function exportReviewTable(){

        $spreadsheetAdapter = new SpreadsheetAdapter();

        $reviewData = $this->reviewRepositoryInterface->getReviewFromVisitIdArrayStudyName($this->visitIdArray, $this->studyName, true);

        //Flatten the nested review status
        $flattenedData = array_map(function($review){
            $reviewData = $review['review_data'];
            unset($review['review_data']);
            return array_merge($review, $reviewData);
        }, $reviewData);

        $investigatorsForms = [];
        $reviewersForms = [];

        foreach($flattenedData as $review){
            if ($review['local']) $investigatorsForms[] = $review;
            else $reviewersForms[] = $review;
        }

        $spreadsheetAdapter->addSheet('InvestigatorsForms');
        $spreadsheetAdapter->fillData('InvestigatorsForms', $investigatorsForms);
        $spreadsheetAdapter->addSheet('ReviewersForms');
        $spreadsheetAdapter->fillData('ReviewersForms', $reviewersForms);

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
