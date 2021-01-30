<?php
/**
 Copyright (C) 2018-2020 KANOUN Salim
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
 * Validate the uploaded dicom, this script act as follow : 
 * Unzip the recieved ZIPs
 * Send each dicom to Orthanc Exposed
 * Produce the Anonymize query in Orthanc Exposed
 * Delete the original import in Orthanc Exposed
 * Send the anonymized study in Orthanc PACS
 * Delete the anonymied dicom in Orthanc Exposed
 * Analyze the Orthanc PACS dicom and write DICOM details in database
 * Update Visit status and send email notifications
 * 
 * Warning : The execution time of this script is long due to Orthanc heavy operations
 * Double check the timeout value that are set in Orthanc class and that database connexion is not timed up
 * Check also the max execution time (which is reset in Orthanc class)
 * 
 */

require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

use App\GaelO\Util;
use GuzzleHttp\Client;

Session::checkSession();
$linkpdo=Session::getLinkpdo();

$timeStamp = time();
$id_visit = $_POST['id_visit'];
$nbOfInstances = $_POST['totalDicomFiles'];
$anonFromOrthancId=$_POST['originalOrthancStudyID'];
$username=$_SESSION['username'];
$study=$_SESSION['study'];
$role=$_SESSION['role'];
$tusFilesID = $_POST['sucessIDsUploaded'];

$unzipedPath = $_SERVER['DOCUMENT_ROOT'].'/data/upload/temp/'.$timeStamp.'_'.$id_visit;

$visitObject=new Visit($id_visit, $linkpdo);
$userObject=new User($username, $linkpdo);

$accessCheck=$userObject->isVisitAllowed($id_visit, User::INVESTIGATOR);
error_log($accessCheck);
error_log($role == User::INVESTIGATOR);
error_log($visitObject->uploadStatus);

if ($accessCheck && $role == User::INVESTIGATOR && $visitObject->uploadStatus == Visit::NOT_DONE) {
	
	//Run as a background task even if the user leave the website
	ignore_user_abort(true);
	//Set Visit as upload processing status
	$visitObject->changeUploadStatus(Visit::UPLOAD_PROCESSING);
    $start_time=microtime(true);
	/**
	 * Try block as each interruption of the proccess must make visit return as upload not done
	 * To allow new upload
	 */
	try {

		if (!is_dir($unzipedPath)) {
			mkdir($unzipedPath, 0755);
		}

		//Unzip each uploaded file and remove them from tus
		foreach($tusFilesID as $fileName){
			$tempZipPath = get_tus_file($fileName);

			$zipSize=filesize($tempZipPath);
			$uncompressedzipSize=get_zip_originalsize($tempZipPath);
			if ($uncompressedzipSize/$zipSize > 50) {
				throw new Exception("Bomb Zip");
			}

			$zip=new ZipArchive;
			$zip->open($tempZipPath);
			$zip->extractTo($unzipedPath);
			$zip->close();
			
			//Remove file from TUS and downloaded temporary zip
			delete_tus_file($fileName);
			unlink($tempZipPath);

		}
		


		//Send unziped files to Orthanc
		$orthancExposedObject=new Orthanc(true);
		$importedMap=sendFolderToOrthanc($unzipedPath, $orthancExposedObject);

		//Anonymize, remove original and send anonymized to Orthanc PACS
		//Read imported map, it only have only one study
		foreach ($importedMap as $studyID=>$seriesIDs) {
			//Anonymize and store new anonymized study Orthanc ID
			$anonymizedIDArray[]=$orthancExposedObject->Anonymize($studyID, $visitObject->getVisitCharacteristics()->anonProfile, $visitObject->patientCode, $visitObject->visitType, $visitObject->study);
			//error_log("Anonymization done at ".(microtime(true)-$start_time));
			//Delete original import
			$orthancExposedObject->deleteFromOrthanc("studies", $studyID);
			
		}
		//Send to Orthanc Pacs and fill the database
		$orthancExposedObject->sendToPeer("OrthancPacs", $anonymizedIDArray);
		//error_log("Peer sent at ".(microtime(true)-$start_time));
		//erase transfered anonymized study from orthanc exposed
		$orthancExposedObject->deleteFromOrthanc("studies", $anonymizedIDArray[0]);
	
		//Fill the Orthanc study / series table
		//Reset the PDO object as the database connexion is likely to be timed out
		$linkpdo=Session::getLinkpdo();
		$fillTable=new Fill_Orthanc_Table($visitObject->id_visit, $username, $linkpdo);
		$studyDetails=$fillTable->parseData($anonymizedIDArray[0]);

		//Check that nb on instances in Orthanc PACS still match the original number of sent instances
		if ($studyDetails['countInstances'] != $nbOfInstances) {
			throw new Exception("Error during Peer transfers"); 
		}
    		
		//Fill Orthanc Tables in Database and update visit status
		$fillTable->fillDB($anonFromOrthancId);
		$logDetails['uploadedSeries']=$studyDetails['seriesInStudy'];
		$logDetails['patientNumber']=$visitObject->patientCode;
		$logDetails['visitType']=$visitObject->visitType;
		$logDetails['modality_visit']=$visitObject->visitGroupObject->groupModality;
		//Log import
		Tracker::logActivity($username, $role, $study, $visitObject->id_visit, "Upload Series", $logDetails);
	
	}catch (Throwable $e1) {
		$logDetails['patientNumber']=$visitObject->patientCode;
		$logDetails['visitType']=$visitObject->visitType;
		$logDetails['modality_visit']=$visitObject->visitGroupObject->groupModality;
		Tracker::logActivity($username, $role, $study, $visitObject->id_visit, "Upload Failure", $logDetails);
	
		error_log($e1->getMessage());
		handleException($e1);
		header('HTTP/1.0 500 Internal Server Error');
	}
		

}else {
	header('HTTP/1.0 403 Forbidden');
	die('You are not allowed to access this file.');
}


/**
 * Send all folder content to Orthanc (recursively)
 * @param string $unzipedPath
 * @param Orthanc $orthancExposedObject
 * @throws Exception
 * @return mixed
 */
function sendFolderToOrthanc(string $unzipedPath, Orthanc $orthancExposedObject) {
	
	global $nbOfInstances;
	//Recursive scann of the unzipped folder
	$rii=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($unzipedPath));
	
	$files=array();
	foreach ($rii as $file) {
		if ($file->isDir()) {
			continue;
		}
		$files[]=$file->getPathname();
    }
    
    if(sizeof($files) != $nbOfInstances){
        throw new Exception("Number Of Uploaded Files dosen't match expected instance number");
    }
	
	$importedMap=null;
	$importedInstances=0;
	//$start_time=microtime(true);
	
	//Import dicom file one by one
	foreach ($files as $file) {
		$importAnswer=$orthancExposedObject->importFileGuzzle($file);
		if (!empty($importAnswer)) {
			$answerdetails=json_decode($importAnswer, true);
			$importedMap[$answerdetails['ParentStudy']][$answerdetails['ParentSeries']][]=$answerdetails['ID'];
			$importedInstances++;
		}
		
	}
	
	//Delete original file after import
	Util::recursiveDirectoryDelete($unzipedPath);
	
	//error_log("Imported ".$importedInstances." files in ".(microtime(true)-$start_time));
	error_log('Imported Instances :'.$importedInstances);
	error_log('Announced number of Instances :'.$nbOfInstances);
	
	if (count($importedMap) == 1 && $importedInstances == $nbOfInstances) {
		return $importedMap;
	}else {
		//These error shall never occur
		if (count($importedMap) > 1) {
			throw new Exception("More than one study in Zip");
		}else if ($importedInstances != $nbOfInstances) {
			throw new Exception("Imported DICOM not matching announced number of Instances");
			
		}
	}
	
}

/**
 * Recursively delete unziped folder
 * @param string $directory
 */
function recursive_directory_delete(string $directory) {
	$it=new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS);
	$it=new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
	foreach ($it as $file) {
		if ($file->isDir()) rmdir($file->getPathname());
		else unlink($file->getPathname());
	}
	rmdir($directory);
}

/**
 * In case of a thrown exception, warn administrator and uploader and set upload to not done
 * @param Exception $e1
 */
function handleException(Throwable $e1) {
	global $visitObject;
	global $linkpdo;

	//If more than own study uploaded or difference of instance number an exception is thrown
	$visitObject->changeUploadStatus(Visit::NOT_DONE);
	warningAdminError($e1->getMessage(), $linkpdo);
}
/**
 * Warn supervisors and uploader that validation of uploaded DICOM has failed
 * @param string $errorMessage
 * @param PDO $linkpdo
 */
function warningAdminError(string $errorMessage, PDO $linkpdo) {
	$sendEmails=new Send_Email($linkpdo);
	global $visitObject;
	global $unzipedPath;
	global $study;
	global $username;

	$sendEmails->addGroupEmails($study, User::SUPERVISOR)->addEmail($sendEmails->getUserEmails($username));
	$sendEmails->sendUploadValidationFailure($visitObject->id_visit, $visitObject->patientCode, $visitObject->visitType,
			$study, $unzipedPath, $username, $errorMessage);
}

/**
 * Get uncompressed Size
 * @param string $filename
 * @return number
 */
function get_zip_originalsize(string $filename) {
	$size=0;
	$resource=zip_open($filename);
	while ($dir_resource=zip_read($resource)) {
		$size+=zip_entry_filesize($dir_resource);
	}
	zip_close($resource);
    
	return $size;
}

function get_tus_file($fileName) {
    
    $client = new Client([
        // Base URI is used with relative requests
		'base_uri' => TUS_SERVER.'/tus/',
		'headers' => ['Tus-Resumable' => '1.0.0']
    ]);
	$downloadedFileName = tempnam(sys_get_temp_dir(), 'dicom');

	$resource  = fopen( $downloadedFileName, 'r+');
	
	$client->request('GET', $fileName, ['sink' => $resource]);

    return $downloadedFileName;
}

function delete_tus_file($fileName){

    $client = new Client([
        // Base URI is used with relative requests
		'base_uri' => TUS_SERVER.'/tus/',
		'headers' => ['Tus-Resumable' => '1.0.0']
    ]);

	$client->request('DELETE', $fileName);
	
    //$code = $response->getStatusCode(); // 200
	//$reason = $response->getReasonPhrase(); // OK

}



