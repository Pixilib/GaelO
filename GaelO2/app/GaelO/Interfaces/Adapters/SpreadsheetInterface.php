<?php

namespace App\GaelO\Interfaces\Adapters;

Interface SpreadsheetInterface{

    public function addSheet(String $title) : void ;

    public function fillData(string $spreadsheetName, array $data) : void ;

    public function writeToExcel() : string ;

    public function writeToCsv(string $sheetName) : string ;
}
