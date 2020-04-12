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
 * Display the review manager, tools to follow review statistics progress
 */

Session::checkSession();
$linkpdo=Session::getLinkpdo();

$userObject=new User($_SESSION['username'], $linkpdo);
$accessCheck=$userObject->isRoleAllowed($_SESSION['study'], $_SESSION['role']);

if ($accessCheck && $_SESSION['role'] == User::SUPERVISOR ) {
    
	$studyObject=new Study($_SESSION['study'], $linkpdo);
	$reviewdetailsMap=$studyObject->getReviewManager()->getReviewsDetailsByVisit();
	
	$usernameCounter=[];
	foreach ($reviewdetailsMap as $visitID=>$details){
		foreach ($details['reviewDoneBy'] as $reviewer){
			$usernameCounter[]=$reviewer;
		}
	}
	
	
    
	//Determine number of review by reviewer
	$uniqueUsers=[];
	$numberOfReads=[];
	$countusername=array_count_values($usernameCounter);
	foreach ($countusername as $key => $value){
		$uniqueUsers[]=$key;
		$numberOfReads[]=$value;
	}
    
    
	require 'views/supervisor/review_manager_view.php';
    
}else {
    
	require 'includes/no_access.php';
}

function generateJSONforDatatable($reviewdetailsMap){
	$newmap=$reviewdetailsMap;
    
	$reviewdetailsArray=null;
	//Transform the hasmap to an array by id Visit
	foreach($newmap as $key => $value){
		$value['reviewNotDoneBy']= implode("/", $value['reviewNotDoneBy']);
		//Implode Reviewer Done to make the final string
		$value['reviewDoneBy']=implode("/", $value['reviewDoneBy']);
		$reviewdetailsArray[]=$value;
        
	}
	return json_encode($reviewdetailsArray);
    
}