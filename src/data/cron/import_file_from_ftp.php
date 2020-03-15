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

$_SERVER['DOCUMENT_ROOT'] ='/gaelo';
require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');
require_once('config_ftp.php');

$linkpdo=Session::getLinkpdo();

echo ('Copy FTP Script Started');

//Get Study Name from command variable
$studyName=$_SERVER['argv'][2];

$ftpReader = new FTP_Reader($linkpdo);    
$ftpReader->setFTPCredential(FTP_HOSTNAME, FTP_USERNAME, FTP_PASSWORD, FTP_PORT, FTP_IS_SFTP);
$ftpReader->setFolder("/".$studyName."_ExportCS");
$ftpReader->setSearchedFile($studyName . '_VISITS.txt');
$ftpReader->setLastUpdateTimingLimit(24 * 60);

try {
    $files = $ftpReader->getFilesFromFTP();
    copy($files[0], $_SERVER['DOCUMENT_ROOT'].$_SERVER['argv'][3]);
} catch (Exception $e) {
    $ftpReader->sendFailedReadFTP($e->getMessage());
    print($e->getMessage());
}




