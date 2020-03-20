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
 **/

/**
 * Output the called Documentation
 */

header( 'content-type: text/html; charset=utf-8' );
require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

Session::checkSession();
$linkpdo=Session::getLinkpdo();

@Session::logInfo('Username : '.$_SESSION['username'].
    ' Role: '.$_SESSION ['role'].' Study: '.$_SESSION['study'].' Documentation ID: '.$_GET['idDocumentation']);

$idDocumentation=$_GET['idDocumentation'];
$documentationObject=new Documentation($linkpdo, $idDocumentation);
$roleAllowed=$documentationObject->isDocumentationAllowedForRole($_SESSION['role']);

$userObject=new User($_SESSION['username'], $linkpdo);
$studyAllowed=$userObject->isRoleAllowed($documentationObject->study, $_SESSION['role']);

if($roleAllowed && $studyAllowed){
    
    header($_SERVER['SERVER_PROTOCOL'].' 200 OK');
    header("Content-Type: application/pdf");
    header("Content-Transfer-Encoding: Binary");
    header("Cache-Control: no-cache");
    header("Content-Length: ".filesize($documentationObject->documentFileLocation));
    header('Content-Disposition: attachment; filename="Documentation-'.$_SESSION['study'].'_'.$documentationObject->documentName.'.pdf"');
    
    set_time_limit(0);
    $file = @fopen($documentationObject->documentFileLocation,"rb");
    if($file){

        while(!feof($file))
        {
            print(@fread($file, 1024*1024));
            flush();
        }

        fclose($file);

    }else{
        throw new Exception("Can't Find Documentation");
    }

    
 
    
    
}else {
    require $_SERVER['DOCUMENT_ROOT'].'/includes/no_access.php';
}