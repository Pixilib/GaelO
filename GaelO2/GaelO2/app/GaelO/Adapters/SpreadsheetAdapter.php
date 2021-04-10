<?php

namespace App\GaelO\Adapters;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SpreadsheetAdapter {

    private Spreadsheet $spreadsheet;

    public function __construct()
    {
        $this->spreadsheet = new Spreadsheet();
        $this->spreadsheet->removeSheetByIndex(0);
    }

    public function addSheet(String $title) : void {
        $workSheet = $this->spreadsheet->createSheet();
        $workSheet->setTitle($title);
    }

    public function fillData(string $spreadsheetName, array $data) : void {
        $inputArray = [];
        if(sizeof($data)>0) $inputArray = $this->generateArrayForSpreadSheet($data);
        $this->spreadsheet->getSheetByName($spreadsheetName)->fromArray($inputArray);
    }

    /**
     * Path should contains filename finishing by .xlsx
     */
    public function writeToExcel() : string {
        $path = $this->createTempFile();
        $writer = new Xlsx($this->spreadsheet);
        $writer->save($path);
        return $path;
    }

    //SK A VOIR
    public function writeToCsv() : array {
        //$workseetIterator = $this->spreadsheet->getWorksheetIterator();
        $path = $this->createTempFile();
        $writer = new CSV($this->spreadsheet);
        $writer->save($path);
        /*
        foreach($workseetIterator as $workSheet){
            $path = $this->createTempFile();
            $writer = new Csv($this->spreadsheet);
            $writer->save($path);
        }*/

        return[''];
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

    private function createTempFile(){
        $tempFile = tmpfile();
        $tempFileMetadata = stream_get_meta_data($tempFile);
        return $tempFileMetadata["uri"];
    }

}
