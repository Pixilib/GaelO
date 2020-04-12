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
 * Main Investigator / Controller / Monitor / Reviewer interface
 * Check role autorization (store acceptance in session variable)
 * Load the JSTree for patient / visit selection
 * Load the requested node (patient/visit) in a div for display
 */

Session::checkSession(true, true);
$linkpdo=Session::getLinkpdo();

//Check permissions
$userObject= new User($_SESSION['username'], $linkpdo);
$connexionPermission=$userObject->isRoleAllowed($_POST['etude'], $_POST['role']);
  
//if requested role is allowed
if($connexionPermission && ($_POST['role'] == User::INVESTIGATOR || $_POST['role'] == User::MONITOR || $_POST['role'] == User::CONTROLLER || $_POST['role'] == User::REVIEWER) ) {
	//Store acceptance in session variable
	$_SESSION['study'] = $_POST['etude'];
	$_SESSION['role'] = $_POST['role'];	
    
	require 'views/investigator/root_investigator_view.php';

} else {
	require 'includes/no_access.php';
}
