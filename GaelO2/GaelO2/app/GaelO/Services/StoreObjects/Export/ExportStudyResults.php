<?php

namespace App\GaelO\Services\StoreObjects\Export;

use ZipArchive;

class ExportStudyResults {

    private ExportPatientResults $exportPatientResults;
    private ExportVisitsResults $exportVisitResults;
    private ExportDicomResults $exportDicomResults;
    private ExportReviewResults $exportReviewResults;
    private ExportTrackerResults $exportTrackerResults;

    public function setExportPatientResults(ExportPatientResults $exportPatientResults){
        $this->exportPatientResults = $exportPatientResults;
    }

    public function setExportDicomResults(ExportDicomResults $exportDicomResults){
        $this->exportDicomResults = $exportDicomResults;
    }

    public function setExportVisitResults(ExportVisitsResults $exportVisitResults){
        $this->exportVisitResults = $exportVisitResults;
    }

    public function setExportReviewResults(ExportReviewResults $exportReviewResults){
        $this->exportReviewResults = $exportReviewResults;
    }

    public function setTrackerReviewResults(ExportTrackerResults $exportTrackerResults){
        $this->exportTrackerResults = $exportTrackerResults;
    }

    public function getPatientExportResults(){
        return $this->exportPatientResults;
    }

    public function getDicomExportResults(){
        return $this->exportDicomResults;
    }

    public function getVisitExportResults(){
        return $this->exportVisitResults;
    }

    public function getReviewExportResults(){
        return $this->exportReviewResults;
    }

    public function getTrackerExportResults(){
        return $this->exportTrackerResults;
    }

    private function getExportResultsObjects() : array {
        return [
            $this->exportPatientResults,
            $this->exportDicomResults,
            $this->exportReviewResults,
            $this->exportVisitResults,
            $this->exportTrackerResults,
        ];
    }

    public function getResultsAsZip() : string {

        $exportResultsObject = $this->getExportResultsObjects();

        $zip=new ZipArchive();
        $tempZip=tempnam( ini_get('upload_tmp_dir'), 'TMP_ZIP_EXPORT_' );
        $zip->open($tempZip, ZipArchive::OVERWRITE);

        foreach($exportResultsObject as $exportObject){

            $dataType = $exportObject->getExportDataType();
            $exportFileXls = $exportObject->getXlsExportFile();
            $zip->addFile($exportFileXls->getPath(), $dataType .'/xls/'.$exportFileXls->getFilename());

            $exportFileCsv = $exportObject->getCsvExportFiles();
            foreach($exportFileCsv as $exportCsv){
                $zip->addFile($exportCsv->getPath(), $dataType .'/csv/'.$exportCsv->getFilename());
            }

        }

        $zip->close();

        return $tempZip;

    }
}
