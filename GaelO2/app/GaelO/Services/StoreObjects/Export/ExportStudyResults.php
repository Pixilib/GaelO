<?php

namespace App\GaelO\Services\StoreObjects\Export;

use ZipArchive;

class ExportStudyResults
{

    private ExportPatientResults $exportPatientResults;
    private ExportVisitsResults $exportVisitResults;
    private ExportDicomResults $exportDicomResults;
    private ExportReviewResults $exportReviewResults;
    private ExportReviewResults $exportInvestigatorFormResults;
    private ExportTrackerResults $exportTrackerResults;
    private ExportUserResults $exportUserResults;
    private ExportFileResults $exportFileResults;

    public function setExportPatientResults(ExportPatientResults $exportPatientResults)
    {
        $this->exportPatientResults = $exportPatientResults;
    }

    public function setExportDicomResults(ExportDicomResults $exportDicomResults)
    {
        $this->exportDicomResults = $exportDicomResults;
    }

    public function setExportVisitResults(ExportVisitsResults $exportVisitResults)
    {
        $this->exportVisitResults = $exportVisitResults;
    }

    public function setExportReviewResults(ExportReviewResults $exportReviewResults)
    {
        $this->exportReviewResults = $exportReviewResults;
    }

    public function setExportInvestigatorFormResults(ExportReviewResults $exportReviewResults)
    {
        $this->exportInvestigatorFormResults = $exportReviewResults;
    }

    public function setTrackerReviewResults(ExportTrackerResults $exportTrackerResults)
    {
        $this->exportTrackerResults = $exportTrackerResults;
    }

    public function setUserResults(ExportUserResults $exportUserResults)
    {
        $this->exportUserResults = $exportUserResults;
    }

    public function setExportFileResults(ExportFileResults $exportFileResults)
    {
        $this->exportFileResults = $exportFileResults;
    }

    public function getPatientExportResults()
    {
        return $this->exportPatientResults;
    }

    public function getDicomExportResults()
    {
        return $this->exportDicomResults;
    }

    public function getVisitExportResults()
    {
        return $this->exportVisitResults;
    }

    public function getReviewExportResults()
    {
        return $this->exportReviewResults;
    }

    public function getTrackerExportResults()
    {
        return $this->exportTrackerResults;
    }

    public function getUserResults()
    {
        return $this->exportUserResults;
    }

    private function getExportResultsObjects(): array
    {
        $exportArray = [
            $this->exportPatientResults ?? null,
            $this->exportDicomResults ?? null,
            $this->exportReviewResults ?? null,
            $this->exportInvestigatorFormResults ?? null,
            $this->exportVisitResults ?? null,
            $this->exportTrackerResults ?? null,
            $this->exportUserResults ?? null,
            $this->exportFileResults ?? null
        ];
        //Filter null ones
        return array_values(array_filter($exportArray));
    }

    public function deleteTemporaryFiles(): void
    {

        $exportResultsObject = $this->getExportResultsObjects();

        foreach ($exportResultsObject as $exportObject) {

            $exportFilesXls = $exportObject->getXlsExportFiles();
            foreach ($exportFilesXls as $exportFileXls) {
                unlink($exportFileXls->getPath());
            }


            $exportFileCsv = $exportObject->getCsvExportFiles();
            foreach ($exportFileCsv as $exportCsv) {
                unlink($exportCsv->getPath());
            }

            $exportFilesZip = $exportObject->getZipExportFiles();
            foreach ($exportFilesZip as $exportZip) {
                unlink($exportZip->getPath());
            }
        }
    }

    public function getResultsAsZip(): string
    {

        $exportResultsObject = $this->getExportResultsObjects();

        $zip = new ZipArchive();
        $tempZip = tempnam(ini_get('upload_tmp_dir'), 'TMP_ZIP_EXPORT_');
        $zip->open($tempZip, ZipArchive::OVERWRITE);

        foreach ($exportResultsObject as $exportObject) {

            $dataType = $exportObject->getExportDataType();
            $exportFilesXls = $exportObject->getXlsExportFiles();
            foreach ($exportFilesXls as $exportFileXls) {
                $zip->addFile($exportFileXls->getPath(), $dataType . '/xls/' . $exportFileXls->getFilename());
            }


            $exportFileCsv = $exportObject->getCsvExportFiles();
            foreach ($exportFileCsv as $exportCsv) {
                $zip->addFile($exportCsv->getPath(), $dataType . '/csv/' . $exportCsv->getFilename());
            }

            $exportFilesZip = $exportObject->getZipExportFiles();
            foreach ($exportFilesZip as $exportZip) {
                $zip->addFile($exportZip->getPath(), $dataType . '/zip/' . $exportZip->getFilename());
            }
        }

        $zip->close();
        $this->deleteTemporaryFiles();
        return $tempZip;
    }
}
