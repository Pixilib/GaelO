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
 * Output the tree structure for JsTree (all roles)
 */

header('content-type: text/html; charset=utf-8');
require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

Session::checkSession();
$linkpdo=Session::getLinkpdo();

$userObject=new User($_SESSION['username'], $linkpdo);
$permissionCheck=$userObject->isRoleAllowed($_SESSION['study'], $_SESSION['role']);

//If permission ok get the tree in JSON
if($permissionCheck){
	$obj = new Tree($_SESSION['role'], $_SESSION['username'], $_SESSION['study'], $linkpdo);
	$tree=$obj -> buildTree();
    
}
//If not permitted or tree query empty, display None in the tree
if (! $permissionCheck || empty($tree)) $tree= array('id'=>0, 'parent'=>"#", "text"=>"None");

//Echo the tree structure for JSTree
echo(json_encode($tree));