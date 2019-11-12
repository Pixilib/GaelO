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

/**
 * Create a new study and define it's visit (form and post processing)
 */

//Only for admin role
if ($_SESSION['admin']) { 

	//Post processing
    if (isset($_POST['studyName'])) {
    	//Add the new study as an active study
        Study::createStudy($_POST['studyName'], $_POST['localFormNeeded'], $_POST['qcNeeded'], $_POST['reviewNeeded'], $_POST['daysLimitInclusion'], $linkpdo);
        
        //Add the visit in study with order
        foreach($_POST['visits'] as $visit){
            $visitData=explode("+Order=", $visit);
            $visitData2=explode("+NumDays=", $visitData[1]);
            
            //Create visit Type entry with specific table
            Visit_Type::createVisit($_POST['studyName'], $visitData[0], $visitData2[0], $visitData2[1], $linkpdo);
            
            $rootSpecificModelsFolder=$_SERVER["DOCUMENT_ROOT"].'/data/form/Poo';
            $rootSpecificScriptFolder=$_SERVER["DOCUMENT_ROOT"].'/data/form/scripts';
            
            //Create root folder if not existing
            if (!is_dir($rootSpecificModelsFolder)) {
                mkdir($rootSpecificModelsFolder, 0777, true);
            }
            
            if (!is_dir($rootSpecificScriptFolder)) {
                mkdir($rootSpecificScriptFolder, 0777, true);
            }
            
            //Create specific file that will need to be edited by user to fill specific data of the forms
            $modelPooFile=$_SERVER["DOCUMENT_ROOT"].'/form_models/study_visit_poo.php';
            $modelScriptFile=$_SERVER["DOCUMENT_ROOT"].'/form_models/study_visit_script.php';
            $destinationPoo=$rootSpecificModelsFolder.DIRECTORY_SEPARATOR.$_POST['studyName']."_".$visitData[0].'.php';
            $destinationScript=$rootSpecificScriptFolder.DIRECTORY_SEPARATOR.$_POST['studyName']."_".$visitData[0].'.php';
            copy($modelPooFile, $destinationPoo);
            copy($modelScriptFile, $destinationScript);
            
        }
        
        //Log the Study Creation
        $actionDetails['details']=$_POST;
        Tracker::logActivity($_SESSION['username'], User::ADMINISTRATOR, $_POST['studyName'], null ,"Create Study", $actionDetails);
        
        
        //Refresh to the main page
        header("Refresh:1;url=../main");
        
    }else{
        
        $allStudiesArray=Global_Data::getAllStudies($linkpdo);
        //Display form
        require('views/administrator/create_study_view.php');
    }
    
}else{
    require 'includes/no_access.php';
}