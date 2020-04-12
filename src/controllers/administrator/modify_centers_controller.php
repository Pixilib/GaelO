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

Session::checkSession();
$linkpdo=Session::getLinkpdo();

if ($_SESSION['admin']) {
	
	//Get all exisitng centers
	$centers=Global_Data::getAllCentersObjects($linkpdo);

  if (isset($_POST['centersData'])) {
      
	$inputDataCenters=json_decode($_POST['centersData'], true);
    
	foreach ($inputDataCenters as $inputCenter){
        
		$centersInputArray[$inputCenter[0]]['name']=$inputCenter[1];
		$centersInputArray[$inputCenter[0]]['country_code']=$inputCenter[2];
	}
    
	//Get existing center in the database
	foreach ($centers as $center){
		$array_center_BDD[$center->code]['name'] = $center->name;
		$array_center_BDD[$center->code]['country_code'] = $center->countryCode;
	}
    
	$insert_centers = @array_diff_assoc($centersInputArray, $array_center_BDD);
    
	//Add missing centers in the database
	foreach ($insert_centers as $code=>$details) {
        
		try{
			Center::addCenter($linkpdo, $code, $details['name'], $details['country_code']);
		}catch (Exception $e1){
			error_log($e1);
			echo(json_encode("Error"));
			return;
		}
        
	}
	//Update modified centers
	$existing_centers = @array_intersect_key($centersInputArray, $array_center_BDD);
	foreach ($existing_centers as $code=>$details) {
		//Select key interestion that have changes name of country
		$isSameName=$centersInputArray[$code]['name']==$array_center_BDD[$code]['name'];
		$isSameCountry=$centersInputArray[$code]['country_code']==$array_center_BDD[$code]['country_code'];
		//Update database
		$modified_centers=null;
        
		if(!$isSameName || !$isSameCountry){
			try{
				$centerObject=new Center($linkpdo, $code);
				$centerObject->updateCenter($centersInputArray[$code]['name'], $centersInputArray[$code]['country_code']);
				$modified_centers[$code]=$centersInputArray[$code];
			}catch (Exception $e1){
				error_log($e1);
				echo(json_encode("Error"));
				return;
			}
            
		}
       
	}
    
	$detail['add_center']=$insert_centers;
	$detail['modified_center']=$modified_centers;
	//Log activity
	Tracker::logActivity($_SESSION['username'], "Administrator", null, null, "Change Center", $detail);

	echo(json_encode("Success"));
    
	} else {
		$countries=Global_Data::getAllcountries($linkpdo);
		require 'views/administrator/modify_centers_view.php';
      
	}
    
} else {
	require 'includes/no_access.php';
}