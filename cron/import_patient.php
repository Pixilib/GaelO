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

$ftpSource=false;

/**
 * Define paramaters :
 * $administratorUsername : username (should exist) used for the import
 * $files : files from soures, use getFilesFromFolders if local path or getFilesFromFTP is remote FTP Server
 */
$administratorUsername="administrator";
//$files=getFilesFromFolder($_SERVER['DOCUMENT_ROOT'].'/cron/test');
//$files=getFilesFromFTP(false, 'localhost', 'user', 'pass', 'path');

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

    }
    
    //erase downloaded source file if from FTP
    if($ftpSource) unlink($file);
}

/**
 * Return temporary files copied from FTP Server
 * @param bool $ssl
 * @param string $host
 * @param string $username
 * @param string $password
 * @param string $path
 * @param int $port
 * @return string[]
 */
function getFilesFromFTP(bool $ssl, string $host, string $username, string $password, string $path='/', int $port=21){
    global $ftpSource;
    $ftpSource=true;
    
    if($ssl){
        $ftpConnect=ftp_ssl_connect ($host, $port) or die("Can't Connect to Server $host");
        
    }else{
        $ftpConnect = ftp_connect($host, $port) or die("Can't Connect to Server $host"); 
    }
    
    if(ftp_login($ftpConnect, $username, $password)){
        
        //Move to the target folder
        if(!ftp_chdir($ftpConnect, $path)){
            die("Can't reach FTP Path Target");
        }
        // Get files in the ftp folder
        $fileContents = ftp_nlist($ftpConnect, ".");
        
        $resultFileArray=[];
        
        foreach ($fileContents as $fileInFtp){
            $temp = fopen(sys_get_temp_dir().DIRECTORY_SEPARATOR.$fileInFtp, 'w');
            ftp_fget($ftpConnect, $temp, $fileInFtp);
            fclose($temp);
            //Store resulting file in array
            $resultFileArray[]=sys_get_temp_dir().DIRECTORY_SEPARATOR.$fileInFtp;
        }
        
        
        ftp_close($ftpConnect);
        return $resultFileArray;
        
    }else{

        ftp_close($ftpConnect);
        die( 'Bad FTP credentials');
    }
}

/**
 * Return files from a local folder
 * @param string $folder
 * @return string[]
 */
function getFilesFromFolder(string $folder){
    
    $scanned_directory = array_diff(scandir($folder), array('..', '.'));
    
    $resultFileArray=[];
    
    foreach ($scanned_directory as $file){
        
        $resultFileArray[]=$importJsonFolderPath.DIRECTORY_SEPARATOR.$file;
        
    }
    
    return $resultFileArray;
    
}

