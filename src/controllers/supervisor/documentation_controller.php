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
 * Documentation edition for a study
 */
Session::checkSession();
$linkpdo=Session::getLinkpdo();

$userObject=new User($_SESSION['username'], $linkpdo);
$accessCheck=$userObject->isRoleAllowed($_SESSION['study'], $_SESSION['role']);

if ($accessCheck && $_SESSION['role'] == User::SUPERVISOR) {
	$studyObject=new Study($_SESSION['study'], $linkpdo);
	$documentationObjects=$studyObject->getDocumentation(User::SUPERVISOR);
    
	if (isset($_POST['validate'])) {

		$actionDetails=[];
        
		//For each documentation we update to the new checkboxes options
		foreach ($documentationObjects as $documentation) {
            
			$investigatorPermissions=false;
			$controllerPermissions=false;
			$monitorPermissions=false;
			$reviewerPermissions=false;
			$deleted=false;
            
			if (isset($_POST['inv'.$documentation->documentId])) {
				$investigatorPermissions=true;
			}
            
			if (isset($_POST['cont'.$documentation->documentId])) {
				$controllerPermissions=true;
			}
            
			if (isset($_POST['mon'.$documentation->documentId])) {
				$monitorPermissions=true;
			}
            
			if (isset($_POST['rev'.$documentation->documentId])) {
				$reviewerPermissions=true;
			}
            
			if (isset($_POST['deleted'.$documentation->documentId])) {
				$deleted=true;
			}
            
			Documentation::updateDocumentation($linkpdo, $documentation->documentId, $investigatorPermissions,
				$monitorPermissions, $controllerPermissions, $reviewerPermissions, $deleted);
            
			//Log Action
			$docUpdateDetails['documentation_id']=$documentation->documentId;
			$docUpdateDetails['name']=$documentation->documentName;
			$docUpdateDetails['version']=$documentation->documentVersion;
			$docUpdateDetails['investigator']=$investigatorPermissions;
			$docUpdateDetails['controller']=$controllerPermissions;
			$docUpdateDetails['monitor']=$monitorPermissions;
			$docUpdateDetails['reviewer']=$reviewerPermissions;
			$docUpdateDetails['deleted']=$deleted;
			$actionDetails[]=$docUpdateDetails;
		}
        
        
		Tracker::logActivity($_SESSION['username'], User::SUPERVISOR, $_SESSION['study'], null, "Update Documentation", $actionDetails);
		echo(json_encode("Success"));
        
	}else {

		require 'views/supervisor/documentation_view.php';
        
	}

    
}else {
	require 'includes/no_access.php';
}