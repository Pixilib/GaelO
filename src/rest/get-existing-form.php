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

/*
 * API to Get existing Form Data
 */

require_once($_SERVER['DOCUMENT_ROOT'].'/rest/check_login.php');

header("Content-Type: application/json; charset=UTF-8");

// get posted data in a PHP Object
$data = json_decode(file_get_contents("php://input"), true);

$id_visit=$data['id_visit'];

$visitAccessCheck=$userObject->isVisitAllowed($id_visit, User::REVIEWER);

if($visitAccessCheck){
    
    $result=[];
    
    $visitObject=new Visit($id_visit, $linkpdo);
    try{
        $reviewObject=$visitObject->queryExistingReviewForReviewer($username);
        $result=$reviewObject->getSpecificData();
        echo(json_encode($result));

    }catch (Exception $e){
        error_log($e->getMessage());
        header('HTTP/1.0 404 Not Found');
    }

    
}else{
    header('HTTP/1.0 403 Forbidden');
    die('You are not allowed to access this file.'); 
    
}