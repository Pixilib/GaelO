<?php

namespace App\GaelO\Services\StoreObjects\Export;

use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class ExportStudyResults {

    private ExportPatientResults $exportPatientResults;
    private ExportVisitsResults $exportVisitResults;
    private ExportDicomResults $exportDicomResults;
    private ExportReviewResults $exportReviewResults;
    private ExportTrackerResults $exportTrackerResults;
    private ExportUserResults $exportUserResults;
    private ExportFileResults $exportFileResults;

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

    public function setUserResults(ExportUserResults $exportUserResults){
        $this->exportUserResults = $exportUserResults;
    }

    public function setExportFileResults(ExportFileResults $exportFileResults){
        $this->exportFileResults = $exportFileResults;
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

    public function getUserResults(){
        return $this->exportUserResults;
    }

    private function getExportResultsObjects() : array {
        return [
            $this->exportPatientResults,
            $this->exportDicomResults,
            $this->exportReviewResults,
            $this->exportVisitResults,
            $this->exportTrackerResults,
            $this->exportUserResults,
            $this->exportFileResults
        ];
    }

    public function getResultsAsZip() : string {

        $exportResultsObject = $this->getExportResultsObjects();

        $zip=new ZipArchive();
        $tempZip=tempnam( ini_get('upload_tmp_dir'), 'TMP_ZIP_EXPORT_' );
        $zip->open($tempZip, ZipArchive::OVERWRITE);

        foreach($exportResultsObject as $exportObject){

            $dataType = $exportObject->getExportDataType();
            $exportFilesXls = $exportObject->getXlsExportFiles();
            foreach($exportFilesXls as $exportFileXls){
                $zip->addFile($exportFileXls->getPath(), $dataType .'/xls/'.$exportFileXls->getFilename());
            }


            $exportFileCsv = $exportObject->getCsvExportFiles();
            foreach($exportFileCsv as $exportCsv){
                $zip->addFile($exportCsv->getPath(), $dataType .'/csv/'.$exportCsv->getFilename());
            }

            $exportFilesZip = $exportObject->getZipExportFiles();
            foreach($exportFilesZip as $exportZip){
                $zip->addFile($exportZip->getPath(), $dataType .'/zip/'.$exportZip->getFilename());
            }

        }

        $zip->close();

        return $tempZip;

    }
}
