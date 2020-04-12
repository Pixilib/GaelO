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
 * Create administrator table displaying all users or a study, and manage opening of "new patient" and "modify patient" form
 */

Session::checkSession();
$linkpdo=Session::getLinkpdo();

if ($_SESSION['admin']) {
	
	$_SESSION['study']=$_POST['study'];
	//Get users data
	if ($_SESSION['study'] == "All Studies") {
		$usersObjects=Global_Data::getAllUsers($linkpdo);
	}else {
		$studyObject=new Study($_SESSION['study'], $linkpdo);
		$usersObjects=$studyObject->getUsersWithRoleInStudy();
	}
	
	require 'views/administrator/user_table_view.php';

}else {
	require 'includes/no_access.php';
}