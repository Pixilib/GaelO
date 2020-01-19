<?php
/**
 Copyright (C) 2018 KANOUN Salim
 This program is free software; you can redistribute it and/or modify
 it under the terms of the Affero GNU General Public v.3 License as published by
 the Free Software Foundation;
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 Affero GNU General Public Public for more details.
 You should have received a copy of the Affero GNU General Public Public along
 with this program; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
 */

/**
 * Automatically import patients defined in JSON for a set of study
 * Define the local or FTP path as source
 * This is called by cron.php script
 */

$_SERVER['DOCUMENT_ROOT'] ='/gaelo';
require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

$linkpdo=Session::getLinkpdo();

$studyName="GATA";
//Should contain study variable name
echo($_SERVER['argv'][1]);

$zipFileData=generateZipExport();

$ftpReader = new FTP_Reader($linkpdo);

try {
    $ftpReader->setFTPCredential();
    $ftpReader->setFolder("/GAELO/".$studyName."/ExportGAELO");
    $ftpReader->writeExportDataFile($studyName, $zipFileData);

} catch (Exception $e) {
    $ftpReader->sendFailedReadFTP($e->getMessage());
    print($e->getMessage());
}

function generateZipExport(){

    global $studyName;
    global $linkpdo;
    $studyObject=new Study($studyName, $linkpdo);

    $exportObject=new Export_Study_Data($studyObject, $linkpdo);

    $patientCsvFile=$exportObject->exportPatientTable();

    $visitCsvFile=$exportObject->exportVisitTable();

    $orthancCsvFile=$exportObject->getImagingData();

    $ReivewCsvFiles=$exportObject->getReviewData();

    //Final ZIP creation
    $zip = new ZipArchive;
    $tempZip = tempnam(ini_get('upload_tmp_dir'), 'TMPZIP_');
    $zip->open($tempZip, ZipArchive::CREATE);
    $zip->addFile($patientCsvFile, "export_patient.csv");
    $zip->addFile($visitCsvFile, "export_visits.csv");
    $zip->addFile($orthancCsvFile, "export_orthanc.csv");
    foreach ($ReivewCsvFiles as $key=>$file){
        $zip->addFile($file, "export_review_$key.csv");
    }
    $zip->close();
    
    return $tempZip;
}