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
 * Upload a file documentation in the upload folder and write it's adress in the database
 */

Session::checkSession();
$linkpdo=Session::getLinkpdo();

$userObject=new User($_SESSION['username'], $linkpdo);
$accessCheck=$userObject->isRoleAllowed($_SESSION['study'], $_SESSION['role']);

if ($accessCheck && $_SESSION['role'] == User::SUPERVISOR) {
    $version=$_POST['version'];

    $uploadDirectoryDestination = ($_SERVER['DOCUMENT_ROOT']."/data/upload/documentation/".$_SESSION['study']."/");
    if (!is_dir($uploadDirectoryDestination)) {
        mkdir( $uploadDirectoryDestination , 0755, true );
    }
    
    //Get the filename and Add "_version" before extension to get unique name
	$uploadedFilename = basename($_FILES['documentationfile']['name']);
	
	$uploadedSize = $_FILES['documentationfile']['size'];
	
	//Accepted upload file
	$extensions = array('.pdf');
	$extension = strrchr($_FILES['documentationfile']['name'], '.');

	//Check Extension
	if(!in_array($extension, $extensions)) {
		$erreur = 'Error : File extension not allowed for upload';
	}
	if($uploadedSize>getMaximumFileUploadSize()) {
		$erreur = 'Error : Uploaded file size over limit';
	}
	//If no error according to size or extension
	if(!isset($erreur)) {
		//Move file to the supervisor/upload folder (return boolean)
	    if(move_uploaded_file($_FILES['documentationfile']['tmp_name'], $uploadDirectoryDestination.$uploadedFilename))
		{
			//Add the new document in the database
		    Documentation::insertDocumentation($linkpdo, $uploadedFilename, $_SESSION['study'], $version);
			
			//Log Activity
			$actionDetails['filename']=$uploadedFilename;
			$actionDetails['vesion']=$version;
			Tracker::logActivity($_SESSION['username'], User::SUPERVISOR, $_SESSION['study'], null, "Add Documentation", $actionDetails);
			
			echo 'Success !';
			header("Refresh:1;url=../index.php");
		//if error when moving file.
		} else {
		    echo 'Failed ! Err'.$_FILES['documentationfile']["error"];
		}
	} else {
		echo $erreur;
	}

}else {
    require 'includes/no_access.php';
}

function getMaximumFileUploadSize()
{
    return min(convertPHPSizeToBytes(ini_get('post_max_size')), convertPHPSizeToBytes(ini_get('upload_max_filesize')));
}

function convertPHPSizeToBytes($sSize)
{
    //
    $sSuffix = strtoupper(substr($sSize, -1));
    if (!in_array($sSuffix,array('P','T','G','M','K'))){
        return (int)$sSize;
    }
    $iValue = substr($sSize, 0, -1);
    switch ($sSuffix) {
        case 'P':
            $iValue *= 1024;
            // Fallthrough intended
        case 'T':
            $iValue *= 1024;
            // Fallthrough intended
        case 'G':
            $iValue *= 1024;
            // Fallthrough intended
        case 'M':
            $iValue *= 1024;
            // Fallthrough intended
        case 'K':
            $iValue *= 1024;
            break;
    }
    return (int)$iValue;
}
