<?php

namespace App\GaelO\Adapters;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SpreadsheetAdapter {

    private Spreadsheet $spreadsheet;

    public function __construct()
    {
        $this->spreadsheet = new Spreadsheet();
    }

    public function setDefaultWorksheetTitle($title) : void {
        $this->spreadsheet->getSheet(0)->setTitle($title);
    }

    public function addSheet(String $title) : void {
        $workSheet = $this->spreadsheet->createSheet();
        $workSheet->setTitle($title);
    }

    public function fillData(string $spreadsheetName, array $data) : void {
        $inputArray = $this->generateArrayForSpreadSheet($data);
        $this->spreadsheet->getSheetByName($spreadsheetName)->fromArray($inputArray);
    }

    /**
     * Path should contains filename finishing by .xlsx
     */
    public function writeToExcel(string $path) : void {
        $writer = new Xlsx($this->spreadsheet);
        $writer->save($path);
    }

    private function generateArrayForSpreadSheet(array $data) : array {

        $resultArray = [];

        //Extract title from the first database row
        $titles = array_keys($data[0]);

        //Generate the title row
        $resultArray[] = $titles;

        //Loop each row of input array
        foreach($data as $rowData){
            $row = [];
            //Loop each key in the title order to generate row of spreadsheet
            foreach($resultArray[0] as $key){
                $row[] = $rowData[$key];
            }
            $resultArray[] = $row;
        }

        return $resultArray;
    }

}
