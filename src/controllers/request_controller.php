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
 * Send message request for administrators of the plateform, handle form display and processing
 */

$linkpdo=Session::getLinkpdo();

//If form sent check form completion
if (isset($_POST['send'])) {
	
	if (!empty($_POST['name']) && !empty($_POST['email']) && !empty($_POST['ic']) && !empty($_POST['request'])) {
		
		$message="The following request was sent and will be processed as soon as possible:<br>
		Name : ".$_POST['name']."<br>
		E-mail : ".$_POST['email']."<br>
		Investigational center : ".$_POST['ic']."<br>
		Request : ".$_POST['request']."<br>";
		
		$mail=new Send_Email($linkpdo);
		$mail->addAminEmails()->addEmail($_POST['email']);
		$mail->sendRequestMessage($_POST['name'], $_POST['email'], $_POST['ic'], $_POST['request']);

	}

} else {
	require 'views/request_view.php';
}
