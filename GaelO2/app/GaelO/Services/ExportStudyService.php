<?php

namespace App\GaelO\Services;

use App\GaelO\Adapters\SpreadsheetAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Repositories\DicomSeriesRepositoryInterface;
use App\GaelO\Interfaces\Repositories\DicomStudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitTypeRepositoryInterface;
use App\GaelO\Services\StoreObjects\Export\ExportDataResults;
use App\GaelO\Services\StoreObjects\Export\ExportDicomResults;
use App\GaelO\Services\StoreObjects\Export\ExportFileResults;
use App\GaelO\Services\StoreObjects\Export\ExportPatientResults;
use App\GaelO\Services\StoreObjects\Export\ExportReviewResults;
use App\GaelO\Services\StoreObjects\Export\ExportStudyResults;
use App\GaelO\Services\StoreObjects\Export\ExportTrackerResults;
use App\GaelO\Services\StoreObjects\Export\ExportUserResults;
use App\GaelO\Services\StoreObjects\Export\ExportVisitsResults;
use App\GaelO\Util;
use ZipArchive;

class ExportStudyService {

    private UserRepositoryInterface $userRepositoryInterface;
    private PatientRepositoryInterface $patientRepositoryInterface;
    private VisitTypeRepositoryInterface $visitTypeRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private DicomStudyRepositoryInterface $dicomStudyRepositoryInterface;
    private DicomSeriesRepositoryInterface $dicomSeriesRepositoryInterface;
    private ReviewRepositoryInterface $reviewRepositoryInterface;
    private ExportStudyResults $exportStudyResults;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private FrameworkInterface $frameworkInterface;

    private string $studyName;

    public function __construct(
        UserRepositoryInterface $userRepositoryInterface,
        PatientRepositoryInterface $patientRepositoryInterface,
        VisitTypeRepositoryInterface $visitTypeRepositoryInterface,
        VisitRepositoryInterface $visitRepositoryInterface,
        DicomStudyRepositoryInterface $dicomStudyRepositoryInterface,
        DicomSeriesRepositoryInterface $dicomSeriesRepositoryInterface,
        ReviewRepositoryInterface $reviewRepositoryInterface,
        TrackerRepositoryInterface $trackerRepositoryInterface,
        ExportStudyResults $exportStudyResults,
        FrameworkInterface $frameworkInterface)
    {
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->patientRepositoryInterface = $patientRepositoryInterface;
        $this->visitTypeRepositoryInterface = $visitTypeRepositoryInterface;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->dicomStudyRepositoryInterface = $dicomStudyRepositoryInterface;
        $this->dicomSeriesRepositoryInterface = $dicomSeriesRepositoryInterface;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
        $this->exportStudyResults = $exportStudyResults;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->frameworkInterface = $frameworkInterface;
    }

    public function setStudyName(string $studyName){
        $this->studyName = $studyName;

        //List Visit Type of this study
        $visitTypes = $this->visitTypeRepositoryInterface->getVisitTypesOfStudy($this->studyName);

        $visitTypeArray = [];

        foreach($visitTypes as $visitType){
            $visitTypeArray[ $visitType['id'] ] = [
                'modality'=>$visitType['visit_group']['modality'],
                'name'=>$visitType['name']
            ];
        }

        $this->visitTypeArray = $visitTypeArray;

        //Store Id of visits of this study
        $this->availableVisits = $this->visitRepositoryInterface->getVisitsInStudy($this->studyName, true, false, true);

        $this->visitIdArray = array_map(function($visit){
            return $visit['id'];
        }, $this->availableVisits);

    }

    public function exportUsersOfStudy() : void {
        $users = $this->userRepositoryInterface->getUsersFromStudy($this->studyName);

        $usersData = [];
        //Select only needed info and concatenate roles in a string
        foreach($users as $user){
            $roles = array_map(function($role){return $role['name'];}, $user['roles']);
            $usersData[] = [
                'id' => $user['id'],
                'lastname' => $user['lastname'],
                'firstname' => $user['firstname'],
                'roles' => implode("/", $roles)
            ];
        }

        $spreadsheetAdapter = new SpreadsheetAdapter();
        $spreadsheetAdapter->addSheet('Users');
        $spreadsheetAdapter->fillData('Users', $usersData);

        $tempFileNameXls = $spreadsheetAdapter->writeToExcel();
        $tempFileNameCsv = $spreadsheetAdapter->writeToCsv('Users');

        $exportPatientResults = new ExportUserResults();
        $exportPatientResults->addExportFile(ExportDataResults::EXPORT_TYPE_XLS, $tempFileNameXls);
        $exportPatientResults->addExportFile(ExportDataResults::EXPORT_TYPE_CSV, $tempFileNameCsv);
        $this->exportStudyResults->setUserResults($exportPatientResults);
    }

    public function exportPatientTable() : void {
        $patientData = $this->patientRepositoryInterface->getPatientsInStudy($this->studyName);
        $spreadsheetAdapter = new SpreadsheetAdapter();
        $spreadsheetAdapter->addSheet('Patients');
        $spreadsheetAdapter->fillData('Patients', $patientData);

        $tempFileNameXls = $spreadsheetAdapter->writeToExcel();
        $tempFileNameCsv = $spreadsheetAdapter->writeToCsv('Patients');

        $exportPatientResults = new ExportPatientResults();
        $exportPatientResults->addExportFile(ExportDataResults::EXPORT_TYPE_XLS, $tempFileNameXls);
        $exportPatientResults->addExportFile(ExportDataResults::EXPORT_TYPE_CSV, $tempFileNameCsv);
        $this->exportStudyResults->setExportPatientResults($exportPatientResults);
    }

    public function exportVisitTable() : void {

        $spreadsheetAdapter = new SpreadsheetAdapter();

        $resultsData = [];

        //Loop each visitType and export data for each one
        foreach($this->availableVisits as $visit){
            //Determine Sheet Name
            $visitTypeDetails = $this->visitTypeArray[ $visit['visit_type']['id'] ];
            $sheetName = $visitTypeDetails['modality'].'_'.$visitTypeDetails['name'];
            unset($visit['visit_type']);
            unset($visit['patient']);
            //transform target_lesions as json string
            $visit['review_status']['target_lesions'] = json_encode($visit['review_status']['target_lesions']);

            $resultsData[$sheetName][]=array_merge( [ 'modality'=> $visitTypeDetails['modality'], 'visit_type' => $visitTypeDetails['name']] , $visit, $visit['review_status'] );

        }

        foreach($resultsData as $sheetName => $value){
            $spreadsheetAdapter->addSheet($sheetName);
            $spreadsheetAdapter->fillData($sheetName, $value);
        }

        //Export created file
        $tempFileNameXls = $spreadsheetAdapter->writeToExcel();

        $exportVisitResults = new ExportVisitsResults();
        $exportVisitResults->addExportFile(ExportDataResults::EXPORT_TYPE_XLS, $tempFileNameXls);

        foreach($resultsData as $sheetName => $value){
            $tempCsvFileName = $spreadsheetAdapter->writeToCsv($sheetName);
            $exportVisitResults->addExportFile(ExportDataResults::EXPORT_TYPE_CSV, $tempCsvFileName, $sheetName);
        }

        $this->exportStudyResults->setExportVisitResults($exportVisitResults);

    }

    public function exportDicomsTable() : void {

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
        $tempFileNameXls = $spreadsheetAdapter->writeToExcel();

        $exportDicomResults = new ExportDicomResults();
        $exportDicomResults->addExportFile(ExportDataResults::EXPORT_TYPE_XLS, $tempFileNameXls);

        $sheets = ['DicomStudies', 'DicomSeries'];
        foreach($sheets as $sheet){
            $tempFileNameCsv = $spreadsheetAdapter->writeToCsv($sheet);
            $exportDicomResults->addExportFile(ExportDataResults::EXPORT_TYPE_CSV, $tempFileNameCsv, $sheet);
        }

        $this->exportStudyResults->setExportDicomResults($exportDicomResults);

    }

    public function exportReviewTable() : void {

        $spreadsheetAdapter = new SpreadsheetAdapter();

        $reviewData = $this->reviewRepositoryInterface->getReviewsFromVisitIdArrayStudyName($this->visitIdArray, $this->studyName, true);

        $localForms = $this->reviewRepositoryInterface->getInvestigatorsFormsFromVisitIdArrayStudyName($this->visitIdArray, $this->studyName, true);

        //Flatten the nested review status
        $reviewersForms = array_map(function($review){
            $reviewData = $review['review_data'];
            $review['sent_files'] = json_encode($review['sent_files']);
            unset($review['review_data']);
            return array_merge($review, $reviewData);
        }, $reviewData);

        $investigatorsForms = array_map(function($review){
            $reviewData = $review['review_data'];
            $review['sent_files'] = json_encode($review['sent_files']);
            unset($review['review_data']);
            return array_merge($review, $reviewData);
        }, $localForms);

        $spreadsheetAdapter->addSheet('InvestigatorsForms');
        $spreadsheetAdapter->fillData('InvestigatorsForms', $investigatorsForms);
        $spreadsheetAdapter->addSheet('ReviewersForms');
        $spreadsheetAdapter->fillData('ReviewersForms', $reviewersForms);

        $tempFileNameXls = $spreadsheetAdapter->writeToExcel();

        $exportReviewResults = new ExportReviewResults();
        $exportReviewResults->addExportFile(ExportDataResults::EXPORT_TYPE_XLS, $tempFileNameXls);

        $sheets = ['InvestigatorsForms', 'ReviewersForms'];
        foreach($sheets as $sheet){
            $tempFileNameCsv = $spreadsheetAdapter->writeToCsv($sheet);
            $exportReviewResults->addExportFile(ExportDataResults::EXPORT_TYPE_CSV, $tempFileNameCsv, $sheet);
        }

        $this->exportStudyResults->setExportReviewResults($exportReviewResults);

    }

    public function exportTrackerTable() : void {

        $spreadsheetAdapter = new SpreadsheetAdapter();

        $roleArray = [Constants::ROLE_INVESTIGATOR, Constants::ROLE_CONTROLLER, Constants::ROLE_REVIEWER, Constants::ROLE_SUPERVISOR];

        foreach($roleArray as $role){
            $trackerData = $this->trackerRepositoryInterface->getTrackerOfRoleAndStudy($this->studyName, $role, false);
            $sheets[] = $role;
            $spreadsheetAdapter->addSheet($role);
            $spreadsheetAdapter->fillData($role, $trackerData);
        }

        $exportTrackerResult = new ExportTrackerResults();

        $tempFileNameXls = $spreadsheetAdapter->writeToExcel();
        $exportTrackerResult->addExportFile(ExportDataResults::EXPORT_TYPE_XLS, $tempFileNameXls);

        foreach($sheets as $sheet){
            $tempFileNameCsv = $spreadsheetAdapter->writeToCsv($sheet);
            $exportTrackerResult->addExportFile(ExportDataResults::EXPORT_TYPE_CSV, $tempFileNameCsv, $sheet);
        }

        $this->exportStudyResults->setTrackerReviewResults($exportTrackerResult);


    }

    public function exportAssociatedFiles() : void {
        $zip=new ZipArchive();
        $tempZip=tempnam(ini_get('upload_tmp_dir'), 'TMPZIP_'.$this->studyName.'_');
        $zip->open($tempZip, ZipArchive::OVERWRITE);
        //Add a file to create zip
        $zip->addFromString('Readme', 'Folder Containing associated files to study');
        //send stored file for this study
        Util::addStoredFilesInZip($zip, $this->studyName);
        $zip->close();

        $exporFileResult = new ExportFileResults();
        $exporFileResult->addExportFile(ExportDataResults::EXPORT_TYPE_ZIP, $tempZip);
        $this->exportStudyResults->setExportFileResults($exporFileResult);
    }

    public function getExportStudyResult () : ExportStudyResults {
        return $this->exportStudyResults;
    }



}
