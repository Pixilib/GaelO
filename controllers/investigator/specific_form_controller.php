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

//Load the specific from in a generic form div which handle form deactivation & unlock / validate buttons depending on review status

Session::checkSession();
$linkpdo=Session::getLinkpdo();

$id_visit=$_POST['id_visit'];
$patient_num=$_POST['patient_num'];
$type_visit=$_POST['type_visit'];
$username= $_SESSION['username'];
$study = $_SESSION['study'];

$userObject=new User($username, $linkpdo);
$permissionResults = $userObject->isVisitAllowed($id_visit, $_SESSION['role']);

$studyObject=new Study($study, $linkpdo);
$formNeeded=$studyObject->formNeeded;

if($permissionResults){
    
    $visitObject=new Visit($id_visit, $linkpdo);
    //Determine if calling local or reviewer form
    $local = ($_SESSION['role']==User::REVIEWER) ? false : true ;
    $formProcessorObject=$visitObject->getFromProcessor($local, $username);
    
    if (!empty($_POST['draft']) || !empty($_POST['validate']) ){
        //Process the form
        if(!empty($_POST['validate'])){
            $validate=true;
        }else {
            $validate=false;
        }
        
        $formProcessorObject->saveForm($_POST, $validate, $linkpdo);
        
    }else{
        //Load Specific form
        
        //If form not needed for investigator, return and do not output the form
        if ( ! $formNeeded && $_SESSION['role']==User::INVESTIGATOR){
            exit();
        }
        
        if($_SESSION['role']==User::INVESTIGATOR || $_SESSION['role']==User::REVIEWER){
            $roleDisable=false;
        }else{
            $roleDisable=true;
        }
        
        //Return local form or user's review according to parameters which retrieved the form processor
        //of this study
        $reviewObject=$formProcessorObject->getSavedForm();
        
        if(!empty($reviewObject)){
            $validatedForm=$reviewObject->validated;
            $results=$reviewObject->getSpecificData();
        }else{
            $validatedForm=false;
        }
        
        //Nothing to display + Form filling not allowed = Nothing to show
        if($roleDisable && empty($results)){
            exit();
        }
        
        require 'views/investigator/specific_form_view.php';
        
    }
    
}else {
    require 'includes/no_access.php';
}

