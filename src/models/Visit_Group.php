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
    public $groupModality;

    public $linkpdo;

    const GROUP_MODALITY_PET = "PT";
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
        $this->groupModality = $visitGroupData['group_modality'];
    }


    public function getVisitType(String $visitName){
        return new Visit_Type($this->linkpdo, $this->groupId, $visitName);
    }

    /**
     * Return all visit type of the current visit group
     */
    public function getAllVisitTypesOfGroup()
    {

        $allVisitsType = $this->linkpdo->prepare('SELECT name FROM visit_type WHERE group_id= :groupId ORDER BY visit_order');
        $allVisitsType->execute(array('groupId' => $this->groupId));
        $allVisits = $allVisitsType->fetchall(PDO::FETCH_COLUMN);

        $visitTypeArray = [];
        foreach ($allVisits as $visitName) {
            $visitTypeArray[] = new Visit_Type($this->linkpdo, $this->groupId, $visitName);
        }

        return $visitTypeArray;
    }

    public function getStudyVisitManager(){
        $studyObject=new Study($this->studyName, $this->linkpdo);
        return new Study_Visit_Manager($studyObject, $this, $this->linkpdo);
    }

    public function getStudy()
    {
        return new Study($this->studyName, $this->linkpdo);
    }

    public static function createVisitGroup(String $studyName, String $groupModality, PDO $linkpdo)
    {

        $req = $linkpdo->prepare('INSERT INTO visit_group (study,  group_modality)
                                      VALUES(:studyName, :groupModality)');

        $req->execute(array(
            'studyName' => $studyName,
            'groupModality' => $groupModality
        ));

        $idGroup = $linkpdo->lastInsertId();

        return new Visit_Group($linkpdo, $idGroup);
    }
}
