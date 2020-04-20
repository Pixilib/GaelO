<?php
/*
 * Copyright (C) 2018-2020 KANOUN Salim
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the Affero GNU General Public v.3 License as published by
 * the Free Software Foundation;
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * Affero GNU General Public Public for more details.
 * You should have received a copy of the Affero GNU General Public Public along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
 */

/**
 * Ask unlock request of investigator/reviewer form, send an email to supervisors for an unlock request message
 * Handle form display and submit processing
 */

Session::checkSession();
$linkpdo=Session::getLinkpdo();

$type_visit=$_POST['type_visit'];
$id_visit=$_POST['id_visit'];
$patient_num=$_POST['patient_num'];
$username=$_SESSION['username'];
$study=$_SESSION['study'];

$userObject=new User($username, $linkpdo);
$patientAllowed=$userObject->isVisitAllowed($id_visit, $_SESSION['role']);

// If permission granted
if (isset($_SESSION['username']) && $patientAllowed) {
    
	// Post processing of form if sent
	if (isset($_POST['validate'])) {
		$request=$_POST['request'];
        
		if (!empty($request)) {
			$emailObject=new Send_Email($linkpdo);
			$emailObject->addGroupEmails($study, User::SUPERVISOR)->addEmail($userObject->userEmail);
			$emailObject->sendUnlockRequestMessage($_SESSION['role'], $username, $type_visit, $patient_num, $study, $request);
            
			$answer="Success";
		}else {
			$answer="Missing Reason";
		}
        
		echo(json_encode($answer));
        
	}else {
		require 'views/investigator/ask_unlock_view.php';
	}
    
}else {
	require 'includes/no_access.php';
}
