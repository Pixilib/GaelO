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

/*
 * API to save invistigator form from external app, intanciate the dedicated object and send the results for database storage
 */

//Access control
require_once($_SERVER['DOCUMENT_ROOT'].'/rest/check_login.php');

// get posted data in a PHP Object
$data=json_decode(file_get_contents("php://input"), true);

$type_visit=$data['type_visit'];
$patient_num=$data['patient_num'];
$id_visit=$data['id_visit'];

// Check reviewer's permissions
$visitAccessCheck=$userObject->isVisitAllowed($id_visit, User::REVIEWER);

if ($visitAccessCheck) {
	//Instanciate the specific object for review management
	$visitObject=new Visit($id_visit, $linkpdo);
	$ReviewObect=$visitObject->getFromProcessor(false, $username);
	$ReviewObect->saveForm($data, $data['validate']);
	$answer="Saved";
	header("Content-Type: application/json; charset=UTF-8");
	echo(json_encode($answer));
	
} else {
	header('HTTP/1.0 403 Forbidden');
}

