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

use GuzzleHttp\Client;

header('content-type: application/json; charset=utf-8');
require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

Session::checkSession();
$linkpdo=Session::getLinkpdo();

$userObject=new User($_SESSION['username'], $linkpdo);

$visitId=$_POST['visit_id'];
$fileKey=$_POST['file_key'];
$tusIds = $_POST['tusIds'];
$nbOfInstances = $_POST['numberOfInstances'];
$timeStamp = time();

//Need to retrieve study before testing permission, can't test visit permissions directly because permission class tests non deleted status
$visitObject=new Visit($visitId, $linkpdo);
$accessCheck=$userObject->isVisitAllowed($visitId, $_SESSION['role']);

if ($accessCheck && in_array($_SESSION['role'], array(User::REVIEWER))) {
	$formProcessor=$visitObject->getFromProcessor(false, $_SESSION['username']);

	if (!$formProcessor instanceof Form_Processor_File) {
		throw new Exception('Wrong From Processor type');
		return json_encode((false));
	}

    $unzipedPath = $_SERVER['DOCUMENT_ROOT'].'/data/upload/temp/'.$timeStamp.'_'.$visitId;
    if (!is_dir($unzipedPath)) {
        mkdir($unzipedPath, 0755);
    }

    //Retrieve ZIPs from TUS and Unzip each uploaded file and remove them from tus
    foreach($tusIds as $tusId){
        $tempZipPath = get_tus_file($tusId);

        $zip=new ZipArchive;
        $zip->open($tempZipPath);
        $zip->extractTo($unzipedPath);
        $zip->close();
        
        //Remove file from TUS and downloaded temporary zip
        delete_tus_file($tusId);
        unlink($tempZipPath);

    }
    


    //Send unziped files to Orthanc temporary
    $orthancExposedObject=new Orthanc(true);
    $importedMap=sendFolderToOrthanc($unzipedPath, $orthancExposedObject);

    $tempFileLocations = [];
    //Retrieve ZIP archive to be stored
    foreach ($importedMap as $studyID=>$seriesIDs) {
		$tempFileLocation=tempnam(ini_get('upload_tmp_dir'), 'TMPZIP_');
        $zipStream=$orthancExposedObject->getZipStreamToFile([$studyID], $tempFileLocation);
		$tempFileLocations[] = $tempFileLocation;
        $orthancExposedObject->deleteFromOrthanc("studies", $studyID);
    }

    //Store ZIP archive with assioated review files
	$fileLocation = $tempFileLocations[0];
    $fileStat = stat($fileLocation);
	$mime = mime_content_type($fileLocation);
    $fileSize = $fileStat['size'];
	
	try{
		$formProcessor->storeManuallyCreatedAssociatedFile($fileKey, $mime, $fileSize, $fileLocation);
		echo( json_encode((true)) );
	}catch (Throwable $t){
		error_log($t->getMessage());
		header('HTTP/1.0 500 Internal Server Error');
		die('You are not allowed to access this file.');
	}

}else {
	header('HTTP/1.0 403 Forbidden');
	die('You are not allowed to access this file.');
}

function get_tus_file($id) {
    
    $client = new Client([
        // Base URI is used with relative requests
		'base_uri' => TUS_SERVER.'/tus/',
		'headers' => ['Tus-Resumable' => '1.0.0']
    ]);
	$downloadedFileName = tempnam(sys_get_temp_dir(), 'dicom');

	$resource  = fopen( $downloadedFileName, 'r+');
	
	$client->request('GET', $id, ['sink' => $resource]);

    return $downloadedFileName;
}

function delete_tus_file($id){

    $client = new Client([
        // Base URI is used with relative requests
		'base_uri' => TUS_SERVER.'/tus/',
		'headers' => ['Tus-Resumable' => '1.0.0']
    ]);

	$client->request('DELETE', $id);

}


function sendFolderToOrthanc(string $unzipedPath, Orthanc $orthancExposedObject) {
	
	global $nbOfInstances;
	//Recursive scan of the unzipped folder
	$rii=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($unzipedPath));
	
	$files=array();
	foreach ($rii as $file) {
		if ($file->isDir()) {
			continue;
		}
		$files[]=$file->getPathname();
    }
    
    if(sizeof($files) != $nbOfInstances){
		error_log('Files :'.sizeof($files));
		error_log('Announced number of Instances :'.$nbOfInstances);
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
	recursive_directory_delete($unzipedPath);
	
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
