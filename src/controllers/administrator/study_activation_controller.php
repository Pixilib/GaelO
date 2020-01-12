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
 * Activate and unactivate studies (a study inactivation let the data in the database but hide it from the
 * platform, act as an archived status). This script handle form and post processing
 */

Session::checkSession();
$linkpdo=Session::getLinkpdo();

//Only for admin role
if ($_SESSION['admin']) { 
    
    //Form Processing
    
    if(!empty($_POST)){
        
        $unvactivatedStudies=[];
        $activatedStudies=[];
        
        if(isset($_POST['activatedStudies'])){
            $activatedStudies=$_POST['activatedStudies'];
        }
        if(isset($_POST['unactivatedStudies'])){
            $unvactivatedStudies=$_POST['unactivatedStudies'];
        }

        foreach ($activatedStudies as $activatedStudy){
            $studyObject=new Study($activatedStudy, $linkpdo);
            $studyObject->changeStudyActivation(true);
        }
        
        foreach ($unvactivatedStudies as $unactivatedStudy){
            $studyObject=new Study($unactivatedStudy, $linkpdo);
            $studyObject->changeStudyActivation(false);  
        }
        
        //Log the Study Creation
        $actionDetails['details']=$_POST;
        Tracker::logActivity($_SESSION['username'], User::ADMINISTRATOR, null , null ,"Change Study Activation", $actionDetails);
        
        echo(json_encode("Success"));
        
    //Display Form
    } else{
    	
    	$studyAllanswers=Global_Data::getAllStudies($linkpdo);
    	$activatedStudiesDb=Global_Data::getAllStudies($linkpdo,true);
    	$unactivatedStudiesDb=array_diff($studyAllanswers, $activatedStudiesDb);
    	
    	require 'views/administrator/study_activation_view.php';
	
    }
}else{
    require 'includes/no_access.php';
}