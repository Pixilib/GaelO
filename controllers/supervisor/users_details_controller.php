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
 * Displays the user details panel
 */

Session::checkSession();
$linkpdo = Session::getLinkpdo();

$userObject= new User($_SESSION['username'], $linkpdo);
$accessCheck = $userObject->isRoleAllowed($_SESSION['study'], $_SESSION['role']);

if ($accessCheck && $_SESSION['role'] == User::SUPERVISOR) {

    $studyObject = new Study($_SESSION['study'], $linkpdo);
    //Get and display all users and roles of this study
    $rolesList = $studyObject->getAllRolesByUsers();
    require 'views/supervisor/users_details_view.php';
    
}else{
    require 'includes/no_access.php';
}