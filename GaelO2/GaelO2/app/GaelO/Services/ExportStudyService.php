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
//Dans Review => Ajouter PatientCode et VisitType (faire un loop dans l'array de visits ?)
//Export en CSV

class ExportStudyService {

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
        $this->availableVisits = $this->visitRepositoryInterface->getVisitsInStudy($this->studyName, true, true);

        $this->visitIdArray = array_map(function($visit){
            return $visit['id'];
        }, $this->availableVisits);

    }

    public function exportPatientTable(){
        $patientData = $this->patientRepositoryInterface->getPatientsInStudy($this->studyName);
        $spreadsheetAdapter = new SpreadsheetAdapter();
        $spreadsheetAdapter->addSheet('Patients');
        $spreadsheetAdapter->fillData('Patients', $patientData);

        $tempFileName = $spreadsheetAdapter->writeToExcel();
        return $tempFileName;
    }

    public function exportVisitTable(){

        $spreadsheetAdapter = new SpreadsheetAdapter();

        $resultsData = [];

        //Loop each visitType and export data for each one
        foreach($this->availableVisits as $visit){
            //Determine Sheet Name
            $visitTypeDetails = $this->visitTypeArray[ $visit['visit_type']['id'] ];
            $sheetName = $visitTypeDetails['modality'].'_'.$visitTypeDetails['name'];

            unset($visit['visit_type']);
            unset($visit['patient']);

            $resultsData[$sheetName][]=array_merge( [ 'modality'=> $visitTypeDetails['modality'], 'visit_type' => $visitTypeDetails['name']] , $visit, $visit['review_status'] );

        }

        foreach($resultsData as $sheetName => $value){
            $spreadsheetAdapter->addSheet($sheetName);
            $spreadsheetAdapter->fillData($sheetName, $value);
        }

        //Export created file
        $tempFileName = $spreadsheetAdapter->writeToExcel();
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
        $tempFileName = $spreadsheetAdapter->writeToExcel();
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

        $tempFileName = $spreadsheetAdapter->writeToExcel();
        return $tempFileName;

    }



}
