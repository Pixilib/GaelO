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
 * Reminder Email function for non uploaded visits
 */

Session::checkSession();
$linkpdo=Session::getLinkpdo();

$userObject=new User($_SESSION['username'], $linkpdo);
$accessCheck=$userObject->isRoleAllowed($_SESSION['study'], $_SESSION['role']);

if ($accessCheck && $_SESSION['role'] == User::SUPERVISOR ) {
	$username = $_SESSION['username'];
    
	$reminderType="Upload";
	$reminderType="Investigator Form";
	$reminderType="Corrective Action";

	$dataVisitsArray=[];
	//SK Array de visits comme le tableau sources

	/*
    //Pour les upload manquants : 
    - Identifier les patients uniques
    - Les regrouper par centre source
    - Lister les visites en should be done avec les details
    - produire le mail
    - envoyer le mail au utilisateur lie a ce centre
    */

	/*
    Pour les local form et corrective action
    - grouper les visites par centre
    - Checker que le statut missing correspond bien à l'action de rappel demandée
    - Produire le mail (seul la 1ere ligne du message change)
    - Envoyer le mail aux utilisateurs lié à ce centre.
    */

	/**
	 * Retour nombre de mails envoyés ?
	 */

	/**
	 * Quid de gestion de plusieurs visites dans un meme mail
	 * Faire de la selection multiple de visites dans la datatable ?
	 */


	$message = '<table><tr>
    <th>Patient Number</th>
    <th>Initials</th>
    <th>Birthdate</th>
    <th>Visit Name</th>
    <th>Theorical Date</th>
    </tr>';

			$message = $message.'<tr>
            <td>'.$patientCode.'</td>
            <td>'.$patientDetails['firstname'].$patientDetails['lastname'].'</td>
            <td>'.$patientDetails['birthdate'].'</td>
            <td>'.$visitName.'</td>
            <td>'.'From '.$details['shouldBeDoneAfter'].' To '.$details['shouldBeDoneBefore'].'</td>
            </tr>';

    
	$message = $message.'</table>';
    
	//Get emails account linked with the current center and respecting the role filter
	$emails=$emailSender->selectDesinatorEmailsfromCenters($_SESSION['study'], $center, $jobs, $linkpdo);

	//Send the email
	$emailSender->setMessage($message);
	$emailSender->sendEmail($emails, "Reminder : Missing Investigator Form", $emailSender->getUserEmails($username));
	$sentEmails++;


	$message = $userText."<br> The investigation form has not been completed or validated for these visits.<br>
    <table>
    <tr>
    <td>Patient Code</td>
    <td>Visit Name</td>
    </tr>";
    
	foreach ($details as $patientCode=>$visitsType){
		$message=$message.'<tr>
        <td>'.$patientCode.'</td>
        <td>'.implode(",", $visitsType).'</td>
        </tr>';
	}
    
	$message=$message.'</table>';
	//List the destinators matching center and jobs requirement
	$emails=$emailSender->selectDesinatorEmailsfromCenters($_SESSION['study'],$center, $jobs, $linkpdo);
	//Send the email
	$emailSender->setMessage($message);
	$emailSender->sendEmail($emails, "Reminder : Missing Investigator Form", $emailSender->getUserEmails($username));
	$sentEmails++;


	$message = $userText."<br> A corrective action was asked, thanks for doing the corrective action.<br>
    <table>
    <tr>
    <td>Patient Code</td>
    <td>Visit Name</td>
    </tr>";
      
		$message=$message.'<tr>
        <td>'.$patientCode.'</td>
        <td>'.implode(",", $visitsType).'</td>
        </tr>';    

      
	  $message=$message.'</table>';
	  //List the destinators matching center and jobs requirement
	  $emails=$emailSender->selectDesinatorEmailsfromCenters($_SESSION['study'], $center, $jobs, $linkpdo);
	  //Send the email
	  $emailSender->setMessage($message);
	  $emailSender->sendEmail($emails, "Reminder : Missing Investigator Form", $emailSender->getUserEmails($username));
	  $sentEmails++;
}