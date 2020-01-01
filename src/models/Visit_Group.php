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
 * Access Data to Visit Group
 */

class Visit_Group
{

    public $groupId;
    public $studyName;
    public $groupType;
    public $groupModality;

    public $linkpdo;

    const GROUP_TYPE_IMAGING = "Imaging";
    const GROUP_TYPE_PATHOLOGY = "Pathology";
    const GROUP_TYPE_RADIOTHERAPY = "Radiotherapy";

    const GROUP_MODALITY_PET = "PET";
    const GROUP_MODALITY_CT = "CT";
    const GROUP_MODALITY_MR = "MR";

    public function __construct(PDO $linkpdo, int $groupId)
    {

        $this->linkpdo = $linkpdo;
        $visitGroupQuery = $this->linkpdo->prepare('SELECT * FROM visit_group WHERE id = :groupId');
        $visitGroupQuery->execute(array('groupId' => $groupId));

        $visitGroupData = $visitGroupQuery->fetch(PDO::FETCH_ASSOC);

        if (empty($visitGroupData)) {
            throw new Exception('No Visit Group Found');
        }

        $this->groupId = $visitGroupData['id'];
        $this->studyName = $visitGroupData['study'];
        $this->groupType = $visitGroupData['group_type'];
        $this->groupModality = $visitGroupData['group_modality'];
    }

    /**
     * Return all visit type of the current visit group
     */
    public function getAllVisitTypesOfGroup()
    {

        $allVisitsType = $this->linkpdo->prepare('SELECT name FROM visit_type WHERE study = :study AND group_id= :groupId ORDER BY visit_order');
        $allVisitsType->execute(array('study' => $this->study, 'groupId' => $this->groupId));
        $allVisits = $allVisitsType->fetchall(PDO::FETCH_COLUMN);

        $visitTypeArray = [];
        foreach ($allVisits as $visitName) {
            $visitTypeArray[] = new Visit_Type($this->linkpdo, $this->groupId, $visitName);
        }

        return $visitTypeArray;
    }

    public function getStudy()
    {
        return new Study($this->studyName, $this->linkpdo);
    }

    public static function createVisitGroup(String $studyName, String $groupType, String $groupModality, PDO $linkpdo)
    {

        $req = $linkpdo->prepare('INSERT INTO visit_group (study, group_type, group_modality)
                                      VALUES(:studyName, :groupType, :groupModality)');

        $req->execute(array(
            'studyName' => $studyName,
            'groupType' => $groupType,
            'groupModality' => $groupModality
        ));

        $idGroup = $linkpdo->lastInsertId();

        return new Visit_Group($linkpdo, $idGroup);
    }
}
