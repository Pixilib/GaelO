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
 * Tracker page showing logs of each role in the current study
 */

Session::checkSession();
$linkpdo=Session::getLinkpdo();

$userObject=new User($_SESSION['username'], $linkpdo);
$accessCheck=$userObject->isRoleAllowed($_SESSION['study'], $_SESSION['role']);

if ($accessCheck && $_SESSION['role'] == User::SUPERVISOR ) {
    $askedRole=ucfirst($_POST['role']);
    if(in_array($askedRole, array(User::INVESTIGATOR, user::CONTROLLER, user::SUPERVISOR, User::REVIEWER))){
        $trackerData=Tracker::getTrackerByRoleStudy($askedRole, $linkpdo, $_SESSION['study']);
        require 'views/supervisor/tracker/tracker_script.php';
        require 'views/supervisor/tracker/tracker_'.(strtolower($askedRole)).'.php';
    } else if($askedRole=="Message"){
        $trackerMessages=Tracker::getMessageStudy($_SESSION['study'], $linkpdo);
        require 'views/supervisor/tracker/tracker_script.php';
        require 'views/supervisor/tracker/tracker_message.php';
    }else{
        require 'includes/no_access.php';
    }
    
}else{
    require 'includes/no_access.php';
}