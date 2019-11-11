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
 * Recive Chunks of upload an reconstruct final uploaded ZIP
 */
define('UPLOADS_DIR', $_SERVER['DOCUMENT_ROOT'] . '/data/upload');
define('UPLOADS_TEMP_DIR', UPLOADS_DIR . '/temp');

require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

Session::checkSession();

try{
    $linkpdo = Session::getLinkpdo();
}catch(Exception $e){
    //Non 200 OK response should ask Resumable to reset the chunk
    //Usefull in case of database resolution failure
    header('HTTP/1.0 210 No Database');
    exit();
}


$userObject = new User($_SESSION['username'], $linkpdo);
$investigatorAccess = $userObject->isRoleAllowed($_SESSION['study'], User::INVESTIGATOR);


if ($investigatorAccess && $_SESSION['role'] == User::INVESTIGATOR) {

	// Check if request is GET and the requested chunk exists or not. This makes testChunks work
	if ($_SERVER['REQUEST_METHOD'] === 'GET') {

		if (!(isset($_GET['resumableIdentifier']) && trim($_GET['resumableIdentifier']) != '')) {
			$_GET['resumableIdentifier'] = '';
		}

		if (!(isset($_GET['resumableFilename']) && trim($_GET['resumableFilename']) != '')) {
			$_GET['resumableFilename'] = '';
		}

		if (!(isset($_GET['resumableChunkNumber']) && trim($_GET['resumableChunkNumber']) != '')) {
			$_GET['resumableChunkNumber'] = '';
		}

		$tempDir = UPLOADS_TEMP_DIR . DIRECTORY_SEPARATOR . $_GET['resumableIdentifier'];
		$chunk_file = $tempDir . DIRECTORY_SEPARATOR . $_GET['resumableFilename'] . '.part' . $_GET['resumableChunkNumber'];
		if (file_exists($chunk_file)) {
			header("HTTP/1.0 200 Ok");
		} else {
			header("HTTP/1.0 404 Not Found");
		}
	}

	// Loop through files and move the chunks to a temporarily created directory
	if (!empty($_FILES)) {
		foreach ($_FILES as $file) {

			// Check errors
			if ($file['error'] !== 0) {
				continue;
			}

			// Init the destination file as <filename.ext>.part<#chunk>
			if (isset($_POST['resumableIdentifier']) && trim($_POST['resumableIdentifier']) != '') {
				$tempDir = UPLOADS_TEMP_DIR . DIRECTORY_SEPARATOR . $_POST['resumableIdentifier'];
			}
			$dest_file = $tempDir . DIRECTORY_SEPARATOR . $_POST['resumableFilename'] . '.part' . $_POST['resumableChunkNumber'];

			// Create the temporary directory
			if (!is_dir($tempDir)) {
				mkdir($tempDir, 0777, true);
			}

			// Move the temporary file
			if (move_uploaded_file($file['tmp_name'], $dest_file)) {
				// Check if all the parts present, and create the final destination file
				createFileFromChunks($tempDir, $_POST['resumableFilename'], $_POST['resumableTotalSize'], $_POST['resumableTotalChunks']);
			}
		}
	}
} else {
    header('HTTP/1.0 403 Forbidden');
}


/**
 * Delete a directory recursively
 */
function rrmdir(string $dir)
{
	if (is_dir($dir)) {
		$objects = scandir($dir);
		foreach ($objects as $object) {
			if ($object != "." && $object != "..") {
				if (filetype($dir . "/" . $object) == "dir") {
					rrmdir($dir . "/" . $object);
				} else {
					unlink($dir . "/" . $object);
				}
			}
		}
		reset($objects);
		rmdir($dir);
	}
}


/**
 * Check if all the parts exist, and gather all the parts of the file together
 * @param string $tempDir - the temporary directory holding all the parts of the file
 * @param string $fileName - the original file name
 * @param string $chunkSize - each chunk size (in bytes)
 * @param string $totalSize - original file size (in bytes)
 */
function createFileFromChunks(string $tempDir, string $fileName, string $totalSize, string $total_files)
{
	// count all the parts of this file
	$total_files_on_server_size = 0;
	$temp_total = 0;
	foreach (scandir($tempDir) as $file) {
		$temp_total = $total_files_on_server_size;
		$tempfilesize = filesize($tempDir . DIRECTORY_SEPARATOR . $file);
		$total_files_on_server_size = $temp_total + $tempfilesize;
	}
	// check that all the parts are present
	// If the Size of all the chunks on the server is equal to the size of the file uploaded.
	if ($total_files_on_server_size >= $totalSize) {

		// create the final destination file 
		$fileFullPath = $tempDir . DIRECTORY_SEPARATOR . $fileName;
		if (($fp = fopen($fileFullPath, 'w')) !== false) {
			for ($i = 1; $i <= $total_files; $i++) {
				fwrite($fp, file_get_contents($tempDir . DIRECTORY_SEPARATOR . $fileName . '.part' . $i));
			}
			fclose($fp);
		} else {
			return false;
		}

		// Move the final destination file and delete temp directory
		rename($fileFullPath, UPLOADS_DIR . DIRECTORY_SEPARATOR . $fileName);
		//rrmdir($tempDir);
		rrmdir(UPLOADS_TEMP_DIR . DIRECTORY_SEPARATOR . $_POST['resumableIdentifier']);
	}
}
