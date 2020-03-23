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
 * Visit information page for a giver visit
 */

Session::checkSession();
$linkpdo=Session::getLinkpdo();

$userObject=new User($_SESSION['username'], $linkpdo);
$accessCheck=$userObject->isVisitAllowed($_POST['id_visit'], $_SESSION['role']);
$visitObject=new Visit($_POST['id_visit'],$linkpdo);

if ($accessCheck && $_SESSION['role'] == User::SUPERVISOR) {
    
	$id_visit = $_POST['id_visit'];
	$visit_type = $visitObject->visitType;
	$patientNumber = $visitObject->patientCode;
	$study = $visitObject->study;
	$data_reviews=[];

	try {
		$localReviewObject=$visitObject->getReviewsObject(true);
		$data_reviews[]=$localReviewObject;
	}catch(Exception $e){
		error_log($e->getMessage());
	}

	try {
		$reviewsNotLocal=$visitObject->getReviewsObject(false);
		array_push($data_reviews, ...$reviewsNotLocal);
	}catch(Exception $e){
		error_log($e->getMessage());
	}
	
	$trackerVisitResponses=Tracker::getTackerForVisit($id_visit, $linkpdo);
	
	require 'views/supervisor/visit_infos_view.php';
	
}else {
    require 'includes/no_access.php';
}
