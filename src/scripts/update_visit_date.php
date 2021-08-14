<?php

/**
 * Update visit date of a visit
 */

require_once($_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php');

Session::checkSession();
$linkpdo = Session::getLinkpdo();

$username = $_SESSION['username'];
$study = $_SESSION['study'];

$visitId = $_POST['visit_id'];
$visitDate = $_POST['visit_date'];
$reason = $_POST['reason'];

$userObject = new User($username, $linkpdo);
$permissionsCheck = $userObject->isVisitAllowed($visitId, User::SUPERVISOR);

//If supervisor session and permission OK
if ($_SESSION['role'] == User::SUPERVISOR && $permissionsCheck) {

    $visitObject = new Visit($visitId, $linkpdo);
    $visitObject->updateVisitAcquisitionDate($visitDate);

    //Log activity
    $actionDetails['type_visit'] = $visitObject->visitType;
    $actionDetails['patient_code'] = $visitObject->patientCode;
    $actionDetails['modality_visit'] = $visitObject->visitGroupObject->groupModality;
    $actionDetails['new_visit_date'] = $visitDate;
    $actionDetails['reason'] = $reason;
    Tracker::logActivity($username, $_SESSION['role'], $study, $visitObject->id_visit, "Update Visit Date", $actionDetails);

} else {
    header('HTTP/1.0 403 Forbidden');
}
