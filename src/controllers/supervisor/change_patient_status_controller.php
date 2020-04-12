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
 * Change patient status to withdraw status
 */

Session::checkSession();
$linkpdo=Session::getLinkpdo();

$userObject=new User($_SESSION['username'], $linkpdo);
$accessCheck=$userObject->isPatientAllowed($_POST['patient_num'], $_SESSION['role']);

if ($accessCheck && $_SESSION['role'] == User::SUPERVISOR) {
    //Query Patient status
    $patientObject=new Patient($_POST['patient_num'], $linkpdo);
    //Process data to write in database
    if (isset($_POST['validate'])) {
            
            $withdraw=$_POST['withdraw'];
            $withdraw_date=$_POST['withdraw_date']; 
            $withdraw_reason=$_POST['reason'];
            
            if ($withdraw) {
                if (!empty($withdraw_date) && !empty($withdraw_reason)) {
                    $patientObject->changeWithdrawStatus(true, $withdraw_date, $withdraw_reason);
                }else {
                    echo(json_encode("Error"));
                    return;
                } 
            }else {
                if (!empty($withdraw_reason)) {
                    $withdraw_date=null;
                    $patientObject->changeWithdrawStatus(false, $withdraw_date, null);
                }else {
                    echo(json_encode("Error"));
                    return;
                }

            }
            
            //Log action
            $actionDetails['patient_code']=$patientObject->patientCode;
            $actionDetails['reason']=$withdraw_reason;
            $actionDetails['withdraw_date']=$withdraw_date;
            $actionDetails['withdraw']=$withdraw;
            Tracker::logActivity($_SESSION['username'], User::SUPERVISOR, $_SESSION['study'], null, "Patient Withdraw", $actionDetails);
            echo(json_encode("Success"));
    }else {
        require 'views/supervisor/change_patient_status_view.php';
    }

} else {
    require 'includes/no_access.php';
}
