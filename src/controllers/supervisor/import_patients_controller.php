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
 * Display the import patients panel
 */

Session::checkSession();
$linkpdo=Session::getLinkpdo();

$userObject=new User($_SESSION['username'], $linkpdo);
$accessCheck=$userObject->isRoleAllowed($_SESSION['study'], $_SESSION['role']);

if ($accessCheck && $_SESSION['role'] == User::SUPERVISOR) {
    
	if (!empty($_POST['json'])) {
		$importPatient=new Import_Patient($_POST['json'], $_SESSION['study'], $linkpdo);
		$importPatient -> readJson();
        
		//Build the Import report to send it by email
		$htmlReport=$importPatient->getHTMLImportAnswer();
		$textReport=$importPatient->getTextImportAnswer();
        
		//log activity
		$actionDetails['Success']=$importPatient->sucessList;
		$actionDetails['Fail']=$importPatient->failList;
		$actionDetails['email']=$textReport;
		Tracker::logActivity($_SESSION['username'], User::SUPERVISOR, $_SESSION['study'], null, "Import Patients", $actionDetails);
        
		//Send the email to administrators of the plateforme
		$email=new Send_Email($linkpdo);
		$email->setMessage($htmlReport);
		$email->addGroupEmails($_SESSION['study'], User::SUPERVISOR);
		$email->setSubject('Import Report');
		$email->sendEmail();
        
		//Print the sent Report in the web page
		echo($htmlReport);
		echo("Report Sent by Email");
        
	}else {
        
		if (GAELO_DATE_FORMAT == 'd.m.Y') {
			$importFormat="DD/MM/YYYY";
            
		}else if (GAELO_DATE_FORMAT == 'm.d.Y') {
			$importFormat="MM/DD/YYYY";
		}
        
		require 'views/supervisor/import_patients_view.php';
	}
    
}else {
	require 'includes/no_access.php';
}
