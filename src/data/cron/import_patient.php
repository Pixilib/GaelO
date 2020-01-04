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

require_once(__DIR__ . '/../../vendor/autoload.php');

echo ('ScriptStarted');

$studyName = "ITSELF";

$ftpReader = new FTP_Reader();
try {
    $ftpReader->setFTPCredential();
    $ftpReader->setFolder("/GAELO/ITSELF/ExportCS");
    $ftpReader->setSearchedFile($studyName . '_PATIENTS.txt');
    $ftpReader->setLastUpdateTimingLimit(24 * 60);
    $files = $ftpReader->getFilesFromFTP();
} catch (Exception $e) {
    print($e->getMessage());
    sendFailedReadFTP();
}

$fileAsString = file_get_contents($files[0]);
$arrayLysarc = $ftpReader::parseLysarcTxt($fileAsString);

print_r($arrayLysarc);
$jsonImport = json_encode($arrayLysarc);
print($jsonImport);

/*

$linkpdo=Session::getLinkpdo();

$importPatient = new Import_Patient($jsonImport, $studyName, $linkpdo);
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
*/

function sendFailedReadFTP(){
    global $linkpdo;
    $email = new Send_Email($linkpdo);
    $email->setMessage("FTP Import Has failed");
    $destinators=$email->getAdminsEmails();
    $email->sendEmail($destinators, 'FTP Import Failed');

}
