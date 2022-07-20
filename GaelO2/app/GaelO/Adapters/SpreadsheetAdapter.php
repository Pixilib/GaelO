<?php

namespace App\GaelO\Adapters;

use App\GaelO\Interfaces\Adapters\SpreadsheetInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SpreadsheetAdapter implements SpreadsheetInterface
{

    private Spreadsheet $spreadsheet;

    public function __construct()
    {
        $this->spreadsheet = new Spreadsheet();
        $this->spreadsheet->removeSheetByIndex(0);
    }

    private function trimIfSheetNameTooLong(string $spreadsheetName): string
    {
        if (strlen($spreadsheetName) > 31) return substr($spreadsheetName, 0, 31);
        else return $spreadsheetName;
    }

    public function addSheet(String $spreadsheetName): void
    {
        //Max length of a sheet is 31 caracters
        $spreadsheetName = $this->trimIfSheetNameTooLong($spreadsheetName);
        $workSheet = $this->spreadsheet->createSheet();
        $workSheet->setTitle($spreadsheetName);
    }

    /**
     * Data should be in key => value array,
     * keys should be identical for all records
     */
    public function fillData(string $spreadsheetName, array $data): void
    {
        $spreadsheetName = $this->trimIfSheetNameTooLong($spreadsheetName);
        $inputArray = [];
        if (sizeof($data) > 0) $inputArray = $this->generateArrayForSpreadSheet($data);
        //Strict null comparison is set to interpret 0 (bool val in db) as not null value
        $this->spreadsheet->getSheetByName($spreadsheetName)->fromArray($inputArray, null, 'A1', true);
    }

    /**
     * Path should contains filename finishing by .xlsx
     */
    public function writeToExcel(): string
    {
        $path = $this->createTempFile();
        $writer = new Xlsx($this->spreadsheet);
        $writer->save($path);
        return $path;
    }

    public function writeToCsv(string $spreadsheetName): string
    {
        $spreadsheetName = $this->trimIfSheetNameTooLong($spreadsheetName);
        $writer = new CSV($this->spreadsheet);
        $index = $this->getSheetIndexByName($spreadsheetName);
        $path = $this->createTempFile();

        $writer->setSheetIndex($index);
        $writer->setDelimiter(',');
        $writer->setEnclosure('"');
        $writer->setLineEnding("\r\n");

        $writer->save($path);
        return $path;
    }

    private function generateArrayForSpreadSheet(array $data): array
    {

        //Generate title using the key of the first record
        $titles = array_keys($data[0]);

        //Generate the title row
        $resultArray[] = $titles;

        //Loop each row of input array
        foreach ($data as $rowData) {
            $row = [];
            //Loop each key in the title order to generate row of spreadsheet
            foreach ($resultArray[0] as $key) {
                $row[] = $rowData[$key] ?? null;
            }
            $resultArray[] = $row;
        }

        return $resultArray;
    }

    private function createTempFile(): string
    {
        $tempFile = tmpfile();
        $tempFileMetadata = stream_get_meta_data($tempFile);
        return $tempFileMetadata["uri"];
    }

    private function getSheetIndexByName(string $sheetName): int
    {
        $workSheet = $this->spreadsheet->getSheetByName($sheetName);
        $index = $this->spreadsheet->getIndex($workSheet);
        return $index;
    }
}
