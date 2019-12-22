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
 * In this path each JSON made for each study should respect '{study}_import.json' name
 * This script has to be triggered by a crontab
 */

require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

$ftpReader=new FTP_Reader();

$ftpReader->setFTPCredential("", "", "", "", 22, true );

$files=$ftpReader->getFilesFromFTP();
print_r($files);
$fileAsString=file_get_contents ( $files[1] );
$arrayLysarc = $ftpReader::parseLysarcTxt($fileAsString);

print_r($arrayLysarc);
print(json_encode($arrayLysarc));

/*
$linkpdo=Session::getLinkpdo();

foreach ($files as $file){
    
    $path_parts = pathinfo($file);
    
    if( !is_dir($file)  && $path_parts['extension']=='json'){
        
        $studyName=strstr($file, "_", true);
        
        $json = file_get_contents($file);
        
        $importPatient = new Import_Patient($json, $studyName, $linkpdo);
        $importPatient -> readJson();
    
        //log activity
        $actionDetails['Success']=$importPatient->sucessList;
        $actionDetails['Fail']=$importPatient->failList;
        $actionDetails['email']=$importPatient->getTextImportAnswer();
        Tracker::logActivity($administratorUsername, User::SUPERVISOR, $studyName , null , "Import Patients", $actionDetails);
        
        //Send the email to administrators of the plateforme
        $email = new Send_Email($linkpdo);
        $email->setMessage($importPatient->getHTMLImportAnswer());
        $destinators=$email->getRolesEmails(User::SUPERVISOR, $studyName);
        $email->sendEmail($destinators, 'Import Report');

    }else if(!is_dir($file)  && $path_parts['extension']=='txt'){

    }
    
    //erase downloaded source file if from FTP
    //if($ftpSource) unlink($file);
}
*/

