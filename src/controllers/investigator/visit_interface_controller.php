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
 * Display the visit interface (when click on a visit node on JSTree), handle many role conditional display of form (controller form...)
 */

Session::checkSession();
$linkpdo=Session::getLinkpdo();

$study=$_SESSION['study'];
$role=$_SESSION['role'];
$username=$_SESSION['username'];

$patient_num=$_POST['patient_num'];
$type_visit=$_POST['type_visit'];
$id_visit=$_POST['id_visit'];

$userObject=new User($username, $linkpdo);
$visitAllowed=$userObject->isVisitAllowed($id_visit, $role);

//If permission granted
if (isset($_SESSION['username']) && $visitAllowed) {
    
	//Retrieve visit Data for quality state
	$visitObject=new Visit($id_visit, $linkpdo);
	$patientObject=new Patient($patient_num, $linkpdo);
	require 'includes/table_visit.php';
	require 'views/investigator/visit_interface_view.php';
   
} else {
	require 'includes/no_access.php';
}
