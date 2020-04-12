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
 * Return if an Orthanc study ID is already known
 */

require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

Session::checkSession();
$linkpdo=Session::getLinkpdo();

$userObject=new User($_SESSION['username'], $linkpdo);
$investigatorAccess=$userObject->isRoleAllowed($_SESSION['study'], User::INVESTIGATOR);

if ($investigatorAccess && $_SESSION['role'] == User::INVESTIGATOR) {
    
	$studyObject=new Study($_SESSION['study'], $linkpdo);
    
	$answer=$studyObject->isOriginalOrthancNeverKnown($_POST['originalOrthancID']);
  
	//Output results
	echo(json_encode($answer));
    
} else {
	echo(json_encode(array("No Access")));
}