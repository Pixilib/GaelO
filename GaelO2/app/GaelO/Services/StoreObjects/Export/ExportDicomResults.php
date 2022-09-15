<?php

namespace App\GaelO\Services\StoreObjects\Export;

use App\GaelO\Exceptions\GaelOException;

class ExportDicomResults extends ExportDataResults {

    private ExportFile $xlsExport;
    private ExportFile $dicomStudyCSV;
    private ExportFile $dicomSeriesCSV;

    const DICOM_STUDIES = 'DicomStudies';
    const DICOM_SERIES = 'DicomSeries';


    public function __construct()
    {
        parent::__construct(parent::EXPORT_TYPE_DICOMS);
    }

    public function addExportFile(string $type, string $path, string $key = null ){

        if($type === ExportDataResults::EXPORT_TYPE_XLS) {
            $this->xlsExport = new ExportFile('export_dicoms.xlsx', $path);
        }else if ($type === ExportDataResults::EXPORT_TYPE_CSV) {
            if($key === self::DICOM_STUDIES) $this->dicomStudyCSV = new ExportFile('export_dicoms_studies.csv', $path);
            else if($key===self::DICOM_SERIES) $this->dicomSeriesCSV = new ExportFile('export_dicoms_series.csv', $path);
            else throw new GaelOException('Unknown File Key');
        }else{
            throw new GaelOException('Unknown File Type');
        }

    }

    public function getXlsExportFiles() : array {
        return [$this->xlsExport];
    }

    public function getCsvExportFiles() : array {
        return [$this->dicomStudyCSV, $this->dicomSeriesCSV];
    }

    public function getZipExportFiles() : array {
        return [];
    }

}
