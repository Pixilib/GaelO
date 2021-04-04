<?php

namespace App\GaelO\Services;

use App\GaelO\Adapters\SpreadsheetAdapter;
use App\GaelO\Interfaces\PatientRepositoryInterface;

class ExportDataService {

    private SpreadsheetAdapter $spreadsheetAdapter;
    private PatientRepositoryInterface $patientRepositoryInterface;
    private String $studyName;

    public function __construct(
        SpreadsheetAdapter $spreadsheetAdapter,
        PatientRepositoryInterface $patientRepositoryInterface)
    {
        $this->spreadsheetAdapter = $spreadsheetAdapter;
        $this->patientRepositoryInterface = $patientRepositoryInterface;
    }

    public function setStudyName(string $studyName){
        $this->studyName = $studyName;
    }

    public function exportPatientTable(){
        $patientData = $this->patientRepositoryInterface->getPatientsInStudy($this->studyName);
        $this->spreadsheetAdapter->setDefaultWorksheetTitle('Patients');
        $this->spreadsheetAdapter->fillData('Patients', $patientData);
        $tempFileName = $this->createTempFile();
        $this->spreadsheetAdapter->writeToExcel($tempFileName);
        return $tempFileName;
    }

    private function createTempFile(){
        $tempFile = tmpfile();
        $tempFileMetadata = stream_get_meta_data($tempFile);
        return $tempFileMetadata["uri"];
    }

    //SK TO BE EXPORTED with deleted rows
    //PatientTable
    //VisitTable (1 spreedsheet by visitType)
    //DicomTable (Studies and Series)
    //ReviewTable (local and Review separated)
    //Associated file to review => SK TODO
}
