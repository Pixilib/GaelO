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

use Ifsnop\Mysqldump as IMysqldump;

Session::checkSession();
$linkpdo=Session::getLinkpdo();

if ($_SESSION['admin']) {

	//Get dump SQL file
	$fileSql=Global_Data::dumpDatabase();
    
	$date=Date('Ymd_his');
    
	$zip=new ZipArchive;
	$tempZip=tempnam(ini_get('upload_tmp_dir'), 'TMPZIPDB_');
	$zip->open($tempZip, ZipArchive::CREATE);
	$zip->addFile($fileSql, "export_database_$date.sql");

	//Export the config files
	exportPath('_config');

	//Export the cron files
	exportPath('cron');

	//Export the form files
	exportPath('form');
    
	//Export the logs files
	exportPath('logs');
    
	//Export the documentation files
	exportPath('upload/documentation');

	//Export Review Associated Files
	exportPath('upload/attached_review_file');
    
	//Export the full list of series in JSON
	$seriesJsonFile=Global_Data::dumpOrthancSeriesJSON($linkpdo);
	$zip->addFile($seriesJsonFile, 'seriesOrthancId.json');
	//Export the full list of centers in JSON
	$centersJsonFile=Global_Data::getAllCentersAsJson($linkpdo);
	$zip->addFile($centersJsonFile, 'centers.json');
    
	$zip->close();
    
	header('Content-type: application/zip');
	header('Content-Disposition: attachment; filename="export_database_'.$date.'.zip"');
	readfile($tempZip);
	unlink($tempZip);
	unlink($fileSql);
    
}else {
	require 'includes/no_access.php';
}

/**
 * Export a source folder to the current zip object
 */
function exportPath(String $pathName) {
	global $zip;

	$relativePath=$_SERVER['DOCUMENT_ROOT'].'/data';
	$absolutePath=realpath($relativePath.'/'.$pathName);
	$fileGenerator=Global_Data::getFileInPath($absolutePath);

	foreach ($fileGenerator as $file) {
		$filePath=$file->getRealPath();
		$subPathDestination=substr($filePath, strlen($relativePath));
		// Add current file to archive
		$zip->addFile($filePath, $subPathDestination);

	}
}