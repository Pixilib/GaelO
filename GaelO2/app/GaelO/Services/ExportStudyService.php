<?php

namespace App\GaelO\Services;

use App\GaelO\Adapters\SpreadsheetAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\Repositories\DicomSeriesRepositoryInterface;
use App\GaelO\Interfaces\Repositories\DicomStudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\StoreObjects\Export\ExportDataResults;
use App\GaelO\Services\StoreObjects\Export\ExportDicomResults;
use App\GaelO\Services\StoreObjects\Export\ExportFileResults;
use App\GaelO\Services\StoreObjects\Export\ExportPatientResults;
use App\GaelO\Services\StoreObjects\Export\ExportReviewDataCollection;
use App\GaelO\Services\StoreObjects\Export\ExportReviewResults;
use App\GaelO\Services\StoreObjects\Export\ExportStudyResults;
use App\GaelO\Services\StoreObjects\Export\ExportTrackerResults;
use App\GaelO\Services\StoreObjects\Export\ExportUserResults;
use App\GaelO\Services\StoreObjects\Export\ExportVisitsResults;
use App\GaelO\Util;
use ZipArchive;

class ExportStudyService
{

    private UserRepositoryInterface $userRepositoryInterface;
    private StudyRepositoryInterface $studyRepositoryInterface;
    private PatientRepositoryInterface $patientRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private DicomStudyRepositoryInterface $dicomStudyRepositoryInterface;
    private DicomSeriesRepositoryInterface $dicomSeriesRepositoryInterface;
    private ReviewRepositoryInterface $reviewRepositoryInterface;
    private ExportStudyResults $exportStudyResults;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    private string $studyName;
    private string $originalStudyName;
    private array $availableVisits;
    private array $visitIdArray;

    public function __construct(
        UserRepositoryInterface $userRepositoryInterface,
        StudyRepositoryInterface $studyRepositoryInterface,
        PatientRepositoryInterface $patientRepositoryInterface,
        VisitRepositoryInterface $visitRepositoryInterface,
        DicomStudyRepositoryInterface $dicomStudyRepositoryInterface,
        DicomSeriesRepositoryInterface $dicomSeriesRepositoryInterface,
        ReviewRepositoryInterface $reviewRepositoryInterface,
        TrackerRepositoryInterface $trackerRepositoryInterface,
        ExportStudyResults $exportStudyResults
    ) {
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->patientRepositoryInterface = $patientRepositoryInterface;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->dicomStudyRepositoryInterface = $dicomStudyRepositoryInterface;
        $this->dicomSeriesRepositoryInterface = $dicomSeriesRepositoryInterface;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
        $this->exportStudyResults = $exportStudyResults;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function setStudyName(string $studyName)
    {
        $this->studyName = $studyName;
        $studyEntity = $this->studyRepositoryInterface->find($this->studyName);
        $this->originalStudyName = $studyEntity->getOriginalStudyName();
        //Store Id of visits of this study
        $this->availableVisits = $this->visitRepositoryInterface->getVisitsInStudy($this->originalStudyName, true, false, false, $this->studyName);

        $this->visitIdArray = array_map(function ($visit) {
            return $visit['id'];
        }, $this->availableVisits);
    }

    public function exportUsersOfStudy(): void
    {
        $users = $this->userRepositoryInterface->getUsersFromStudy($this->studyName, false);

        $usersData = [];
        //Select only needed info and concatenate roles in a string
        foreach ($users as $user) {
            $roles = array_map(function ($role) {
                return $role['name'];
            }, $user['roles']);
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

    public function exportPatientTable(): void
    {
        $patientData = $this->patientRepositoryInterface->getPatientsInStudy($this->originalStudyName, false);
        foreach($patientData as &$patient){
            //Metadata is an array which need to be serialized back to a string
            $patient['metadata'] = json_encode($patient['metadata']);
        }
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

    public function exportVisitTable(): void
    {

        $spreadsheetAdapter = new SpreadsheetAdapter();

        $resultsData = [];

        //Loop each visitType and export data for each one
        foreach ($this->availableVisits as $visit) {
            $visitTypeName = $visit['visit_type']['name'];
            $visitGroupName = $visit['visit_type']['visit_group']['name'];
            //Determine Sheet Name
            $sheetName = $visitGroupName . '_' . $visitTypeName;
            unset($visit['visit_type']);
            unset($visit['patient']);
            //transform target_lesions as json string
            $visit['review_status']['target_lesions'] = json_encode($visit['review_status']['target_lesions']);

            $resultsData[$sheetName][] = array_merge(['visit_group' => $visitGroupName, 'visit_type' => $visitTypeName], $visit, $visit['review_status']);
        }

        foreach ($resultsData as $sheetName => $value) {
            $spreadsheetAdapter->addSheet($sheetName);
            $spreadsheetAdapter->fillData($sheetName, $value);
        }

        //Export created file
        $tempFileNameXls = $spreadsheetAdapter->writeToExcel();

        $exportVisitResults = new ExportVisitsResults();
        $exportVisitResults->addExportFile(ExportDataResults::EXPORT_TYPE_XLS, $tempFileNameXls);

        foreach ($resultsData as $sheetName => $value) {
            $tempCsvFileName = $spreadsheetAdapter->writeToCsv($sheetName);
            $exportVisitResults->addExportFile(ExportDataResults::EXPORT_TYPE_CSV, $tempCsvFileName, $sheetName);
        }

        $this->exportStudyResults->setExportVisitResults($exportVisitResults);
    }

    public function exportDicomsTable(): void
    {

        $spreadsheetAdapter = new SpreadsheetAdapter();

        $dicomStudyData = $this->dicomStudyRepositoryInterface->getDicomStudyFromVisitIdArray($this->visitIdArray, false);
        $spreadsheetAdapter->addSheet('DicomStudies');
        $spreadsheetAdapter->fillData('DicomStudies', $dicomStudyData);

        $studyInstanceUIDArray = array_map(function ($studyEntity) {
            return $studyEntity['study_uid'];
        }, $dicomStudyData);

        //Get Series data for series spreadsheet
        $dicomSeriesData = $this->dicomSeriesRepositoryInterface->getDicomSeriesOfStudyInstanceUIDArray($studyInstanceUIDArray, false);
        $spreadsheetAdapter->addSheet('DicomSeries');
        $spreadsheetAdapter->fillData('DicomSeries', $dicomSeriesData);

        //Export created file
        $tempFileNameXls = $spreadsheetAdapter->writeToExcel();

        $exportDicomResults = new ExportDicomResults();
        $exportDicomResults->addExportFile(ExportDataResults::EXPORT_TYPE_XLS, $tempFileNameXls);

        $sheets = ['DicomStudies', 'DicomSeries'];
        foreach ($sheets as $sheet) {
            $tempFileNameCsv = $spreadsheetAdapter->writeToCsv($sheet);
            $exportDicomResults->addExportFile(ExportDataResults::EXPORT_TYPE_CSV, $tempFileNameCsv, $sheet);
        }

        $this->exportStudyResults->setExportDicomResults($exportDicomResults);
    }

    public function exportReviewerForms(): void
    {
        //Get reviews of the visits of this study
        $reviewEntities = $this->reviewRepositoryInterface->getReviewsFromVisitIdArrayStudyName($this->visitIdArray, $this->studyName, false);
        $this->groupReviewPerVisitType($reviewEntities, Constants::ROLE_REVIEWER);
    }

    public function exportInvestigatorForms(): void
    {
        //Get investigator forms of the visits of this study
        $investigatorForms = $this->reviewRepositoryInterface->getInvestigatorsFormsFromVisitIdArrayStudyName($this->visitIdArray, $this->originalStudyName, false);
        $this->groupReviewPerVisitType($investigatorForms, Constants::ROLE_INVESTIGATOR);
    }

    public function exportAll() :void
    {
        $this->exportPatientTable();
        $this->exportVisitTable();
        $this->exportDicomsTable();
        $this->exportInvestigatorForms();
        $this->exportReviewerForms();
        $this->exportTrackerTable();
        $this->exportUsersOfStudy();
        $this->exportAssociatedFiles();
    }

    private function groupReviewPerVisitType(array $reviewEntities, string $role): void
    {

        $exportReviewDataCollection = new ExportReviewDataCollection($this->studyName, $role);

        //Sort review into object to isolate each visit results
        foreach ($reviewEntities as $reviewEntity) {
            $visitTypeName = $reviewEntity['visit']['visit_type']['name'];
            $visitGroupName = $reviewEntity['visit']['visit_type']['visit_group']['name'];
            $exportReviewDataCollection->addData($visitGroupName, $visitTypeName, $reviewEntity);
        }

        $exportReviewResults = new ExportReviewResults();
        $spreadsheetAdapter = new SpreadsheetAdapter();

        $dataCollection = $exportReviewDataCollection->getCollection();

        //Treat each visits type review's
        foreach ($dataCollection as $exportReviewData) {
            $visitGroupName = $exportReviewData->getVisitGroupName();
            $visitTypeName = $exportReviewData->getVisitTypeName();

            //Only put 3 first letter of role (Inv or Rev) to reduce the risk of lenth > 31 caractere
            $sheetName =  substr($role, 0, 3)  . '_' . $visitGroupName . '_' . $visitTypeName;

            //get formatted date from export review data
            $data = $exportReviewData->getData();
            $spreadsheetAdapter->addSheet($sheetName);
            $spreadsheetAdapter->fillData($sheetName, $data);

            $tempCsvFileName = $spreadsheetAdapter->writeToCsv($sheetName);
            $exportReviewResults->addExportFile(ExportDataResults::EXPORT_TYPE_CSV, $tempCsvFileName, $sheetName);
        }

        $tempFileNameXls = $spreadsheetAdapter->writeToExcel();
        $exportReviewResults->addExportFile(ExportDataResults::EXPORT_TYPE_XLS, $tempFileNameXls, substr($role, 0, 3));
        if ($role === Constants::ROLE_REVIEWER) {
            $this->exportStudyResults->setExportReviewResults($exportReviewResults);
        }
        if ($role === Constants::ROLE_INVESTIGATOR) {
            $this->exportStudyResults->setExportInvestigatorFormResults($exportReviewResults);
        }
    }

    public function exportTrackerTable(): void
    {

        $spreadsheetAdapter = new SpreadsheetAdapter();

        $roleArray = [Constants::ROLE_INVESTIGATOR, Constants::ROLE_CONTROLLER, Constants::ROLE_REVIEWER, Constants::ROLE_SUPERVISOR];

        foreach ($roleArray as $role) {
            $trackerData = $this->trackerRepositoryInterface->getTrackerOfRoleAndStudy($this->studyName, $role, false);
            $trackerData = array_map(function ($trackerEntity) {
                $trackerEntity['action_details'] = json_encode($trackerEntity['action_details']);
                return $trackerEntity;
            }, $trackerData);
            $sheets[] = $role;
            $spreadsheetAdapter->addSheet($role);
            $spreadsheetAdapter->fillData($role, $trackerData);
        }

        $exportTrackerResult = new ExportTrackerResults();

        $tempFileNameXls = $spreadsheetAdapter->writeToExcel();
        $exportTrackerResult->addExportFile(ExportDataResults::EXPORT_TYPE_XLS, $tempFileNameXls);

        foreach ($sheets as $sheet) {
            $tempFileNameCsv = $spreadsheetAdapter->writeToCsv($sheet);
            $exportTrackerResult->addExportFile(ExportDataResults::EXPORT_TYPE_CSV, $tempFileNameCsv, $sheet);
        }

        $this->exportStudyResults->setTrackerReviewResults($exportTrackerResult);
    }

    public function exportAssociatedFiles(): void
    {
        $zip = new ZipArchive();
        $tempZip = tempnam(ini_get('upload_tmp_dir'), 'TMPZIP_' . $this->studyName . '_');
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

    public function getExportStudyResult(): ExportStudyResults
    {
        return $this->exportStudyResults;
    }
}
