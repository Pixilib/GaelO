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
 * List all Visit waiting reviews for the user accross all studies
 */

require_once($_SERVER['DOCUMENT_ROOT'] . '/rest/check_login.php');

$visitsResults = [];

$possibleStudyList = $userObject->getRolesMap();

foreach ($possibleStudyList as $study => $roles) {
    //In Each study with role consider studies where the user is reviewer
    if (in_array(User::REVIEWER, $roles)) {

        //Get Tree for role reviewer
        $studyObject = new Study($study, $linkpdo);
        $treeObject = new Tree(User::REVIEWER, $username, $study, $linkpdo);
        $treeItemArray = $treeObject->buildTree();

        foreach ($treeItemArray as $item) {

            if ($item['level'] == 'visit') {

                //create a patient entry
                $visitObject = new Visit($item['id'], $linkpdo);
                $visitDetails['patientCode'] = $visitObject->patientCode;
                $visitDetails['idVisit'] = $visitObject->id_visit;
                $visitDetails['visitType'] = $visitObject->visitType;
                $visitDetails['visitStatus'] = $visitObject->reviewStatus;
                $visitDetails['visitModality'] = $visitObject->visitGroupObject->groupModality;

                $dicomDetailsObject = $visitObject->getStudyDicomDetails();
                $visitDetails['studyDate'] = $dicomDetailsObject->studyAcquisitionDate;
                $visitDetails['studyUID'] = $dicomDetailsObject->studyUID;

                $visitsResults[$study][] = $visitDetails;
            }
        }

        
    }
}

header("Content-Type: application/json; charset=UTF-8");
if(empty($visitsResults)) echo(json_encode (json_decode ("{}")));
else echo (json_encode($visitsResults));
