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
require_once('config_ftp.php');

$linkpdo=Session::getLinkpdo();

//Get Study Name from command variable
$studyName=$_SERVER['argv'][2];
echo($studyName);
//Generate the temporary file to be exported
$fileArray=generateExportFile();
echo(implode(',',$fileArray));

//Send process to destination
try {
    $ftpReader = new FTP_Reader($linkpdo);
    $ftpReader->setFTPCredential(FTP_HOSTNAME, FTP_USERNAME, FTP_PASSWORD, FTP_PORT, FTP_IS_SFTP);
    $ftpReader->setFolder("/GAELO/".$studyName."/ExportGAELO");
    foreach($fileArray as $fileName => $fileToUpload){

        $results=$ftpReader->writeExportDataFile($fileName.'.csv', $fileToUpload);

    }
    

} catch (Exception $e) {
    echo('Failure');
    echo($e->getMessage());
    $ftpReader->sendFailedReadFTP($e->getMessage());
}

function generateExportFile(){

    global $studyName;
    global $linkpdo;

    $studyObject=new Study($studyName, $linkpdo);

    $exportObject=$studyObject->getExportStudyData();

    $fileArray=[];

    $fileArray['Export_Patients']=$exportObject->exportPatientTable();

    $fileArray['Export_Visits']=$exportObject->exportVisitTable();

    $fileArray['Export_DICOM']=$exportObject->getImagingData();

    $reviewsFiles=$exportObject->getReviewData();

    foreach($reviewsFiles as $key => $file){

        $fileArray['Export_Review_'.$key]=$file;

    }
    
    return $fileArray;
}