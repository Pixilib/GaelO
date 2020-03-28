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

require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

Session::checkSession();
$linkpdo=Session::getLinkpdo();

$userObject=new User($_SESSION['username'], $linkpdo);

$reviewId=$_POST['review_id'];
$fileKey=$_POST['file_key'];
$local = $_SESSION['role'] == User::INVESTIGATOR ? true : false ; 

$reviewObject = new Review($reviewId, $linkpdo);

//Need to retrieve study before testing permission, can't test visit permissions directly because permission class tests non deleted status
$visitObject=new Visit($reviewObject->id_visit, $linkpdo);
$accessCheck=$userObject->isRoleAllowed($visitObject->study, $_SESSION['role']);

if ($accessCheck && in_array($_SESSION['role'], array(User::INVESTIGATOR, User::REVIEWER)) ) {

    $filePath = $reviewObject->getAssociatedFilePath($fileKey);

    header('Content-type: application/octet-stream; charset=utf-8' );
    header("Content-Transfer-Encoding: Binary");
    header("Cache-Control: no-cache");
    header("Content-Length: ".filesize($filePath));
    header('Content-Disposition: attachment; filename="'.basename($filePath).'"');
    $file = @fopen($filePath,"rb");
    if($file){
        while(!feof($file))
        {
            print(@fread($file, 1024*1024));
            flush();
        }
        fclose($file);
    }else{
        throw new Exception("Can't Find Attached File");
    }

} else {
    header('HTTP/1.0 403 Forbidden');
	die('You are not allowed to access this file.');
}