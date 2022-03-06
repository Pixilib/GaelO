<?php

/**
 Copyright (C) 2018-2020 KANOUN Salim
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

	const GROUP_MODALITY_PET="PT";
	const GROUP_MODALITY_CT="CT";
	const GROUP_MODALITY_MR="MR";
	const GROUP_MODALITY_RTSTRUCT = "RTSTRUCT";
	const GROUP_MODALITY_OP = "OP";
	const GROUP_MODALITY_NM="NM";

	public function __construct(PDO $linkpdo, int $groupId)
	{

		$this->linkpdo=$linkpdo;
		$visitGroupQuery=$this->linkpdo->prepare('SELECT * FROM visit_group WHERE id = :groupId');
		$visitGroupQuery->execute(array('groupId' => $groupId));

		$visitGroupData=$visitGroupQuery->fetch(PDO::FETCH_ASSOC);

		if (empty($visitGroupData)) {
			throw new Exception('No Visit Group Found');
		}

		$this->groupId=$visitGroupData['id'];
		$this->studyName=$visitGroupData['study'];
		$this->groupModality=$visitGroupData['group_modality'];
	}

	/**
	 * Get Visit Type object from Visit Name in this group
	 */
	public function getVisitType(String $visitName): Visit_Type
	{
		return Visit_Type::getVisitTypeByName($this->groupId, $visitName, $this->linkpdo);
	}

	/**
	 * Return all visit type of the current visit group
	 */
	public function getAllVisitTypesOfGroup(): array
	{

		$allVisitsType=$this->linkpdo->prepare('SELECT id FROM visit_type WHERE group_id= :groupId ORDER BY visit_order');
		$allVisitsType->execute(array('groupId' => $this->groupId));
		$allVisits=$allVisitsType->fetchall(PDO::FETCH_COLUMN);

		$visitTypeArray=[];
		foreach ($allVisits as $visitTypeId) {
			$visitTypeArray[]=new Visit_Type($this->linkpdo, $visitTypeId);
		}

		return $visitTypeArray;
	}

	/**
	 * Get iterator for Visit Type in this group
	 */
	public function getAllVisitTypesOfGroupIterator(): Visit_Type_Iterator
	{
		return new Visit_Type_Iterator($this->getAllVisitTypesOfGroup());
	}

	/**
	 * Get Study Visit manager of this group
	 */
	public function getStudyVisitManager(): Group_Visit_Manager
	{
		$studyObject=new Study($this->studyName, $this->linkpdo);
		return new Group_Visit_Manager($studyObject, $this, $this->linkpdo);
	}

	/**
	 * Get study Object of this group
	 */
	public function getStudy(): Study
	{
		return new Study($this->studyName, $this->linkpdo);
	}

	/**
	 * Create a new Visit Group
	 */
	public static function createVisitGroup(String $studyName, String $groupModality, PDO $linkpdo): Visit_Group
	{

		$req=$linkpdo->prepare('INSERT INTO visit_group (study,  group_modality)
                                      VALUES(:studyName, :groupModality)');

		$req->execute(array(
			'studyName' => $studyName,
			'groupModality' => $groupModality
		));

		$idGroup=$linkpdo->lastInsertId();

		return new Visit_Group($linkpdo, $idGroup);
	}
}
