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
 * Get Orthanc Job Progression
 */

require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

Session::checkSession();
$linkpdo=Session::getLinkpdo();

$userObject=new User($_SESSION['username'], $linkpdo);
$studyAllowed=$userObject->isRoleAllowed($_SESSION['study'], $_SESSION['role']);

if ($studyAllowed && $_SESSION['role'] == User::SUPERVISOR) {

	$orthancObject=new Orthanc();
	$jobInfos=$orthancObject->getJobDetails($_POST['jobId']);
    
	echo(json_encode($jobInfos));
    
}else {
	header('HTTP/1.0 403 Forbidden');
	die('You are not allowed to access this file.');
}

?>