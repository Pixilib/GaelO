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
 * Acces data of Visit Type table
 */
Class Visit_Type {
    
	public $id;
	public $groupId;
	public $name;
	public $tableReviewSpecificName;
	public $visitOrder;
	public $localFormNeeded;
	public $qcNeeded;
	public $reviewNeeded;
	public $optionalVisit;
	public $limitLowDays;
	public $limitUpDays;
	public $anonProfile;
	public $dicomConstraints;

	public $linkpdo;
    
	public function __construct(PDO $linkpdo, int $visitTypeId) {
        
		$this->linkpdo=$linkpdo;
		$visitTypeQuery=$this->linkpdo->prepare('SELECT * FROM visit_type WHERE id = :visitTypeId');
		$visitTypeQuery->execute(array('visitTypeId' => $visitTypeId));
		$visitType=$visitTypeQuery->fetch(PDO::FETCH_ASSOC);

		$this->id=$visitType['id'];
		$this->groupId=$visitType['group_id'];
		$this->name=$visitType['name'];
		$this->tableReviewSpecificName=$visitType['table_review_specific'];
		$this->visitOrder=$visitType['visit_order'];
		$this->localFormNeeded=$visitType['local_form_needed'];
		$this->qcNeeded=$visitType['qc_needed'];
		$this->reviewNeeded=$visitType['review_needed'];
		$this->optionalVisit=$visitType['optional'];
		$this->limitLowDays=$visitType['limit_low_days'];
		$this->limitUpDays=$visitType['limit_up_days'];
		$this->anonProfile=$visitType['anon_profile'];
		$this->dicomConstraints = $visitType['dicom_constraints'];
        
	}

	public static function getVisitTypeByName($groupId, String $visitName, PDO $linkpdo) : Visit_Type {
		$visitTypeQuery=$linkpdo->prepare('SELECT id FROM visit_type WHERE group_id = :groupId AND name= :name');
		$visitTypeQuery->execute(array('groupId' => $groupId, 'name'=>$visitName));
		$visitTypeId=$visitTypeQuery->fetch(PDO::FETCH_COLUMN);
		return new Visit_Type($linkpdo, $visitTypeId);

	}

	public function getDicomContraintsArray(){
		return $this->dicomConstraints !=null ? json_decode($this->dicomConstraints) : null;
	}
    
	/**
	 * Return name of specific table of this visit type
	 * @return array
	 */
	public function getSpecificFormColumn() : Array {
		$visitsTypeColumnQuery=$this->linkpdo->prepare('SELECT `COLUMN_NAME`
                                            FROM `INFORMATION_SCHEMA`.`COLUMNS`
                                            WHERE  `TABLE_NAME`="'.$this->tableReviewSpecificName.'"');
		$visitsTypeColumnQuery->execute();
		$columnsSpecific=$visitsTypeColumnQuery->fetchAll(PDO::FETCH_COLUMN);
        
		return $columnsSpecific;
	}
    
	/**
	 * Return name and type of the specific table of this visit
	 * @return array
	 */
	public function getSpecificTableInputType() : Array {
		$query=$this->linkpdo->prepare('SELECT COLUMN_NAME, COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME="'.$this->tableReviewSpecificName.'"');
		$query->execute();
		$datas=$query->fetchAll(PDO::FETCH_ASSOC);
        
		return $datas;
        
	}

	public function getVisitGroup() : Visit_Group{
		return new Visit_Group($this->linkpdo, $this->groupId);
	}
    
	public static function createVisitType(string $studyName, Visit_Group $visitGroup, String $visitName, int $order, int $limitLowDays, int $limitUpDays, bool $localFormNeed, bool $qcNeeded, bool $reviewNeeded, bool $optional, String $anonProfile, array $dicomConstraints, PDO $linkpdo) {
        
		$req=$linkpdo->prepare('INSERT INTO visit_type (group_id, name, table_review_specific, visit_order, local_form_needed, qc_needed, review_needed, optional, limit_low_days, limit_up_days, anon_profile, dicom_constraints)
                                      VALUES(:groupId, :visitName, :tableSpecific, :order, :localFormNeeded, :qcNeeded, :reviewNeeded, :optional, :limitLowDays, :limitUpDays, :anonProfile, :dicomConstraints)');
        
		$tableSpecificName=$visitGroup->groupModality."_".$studyName."_".$visitName;

		$req->execute(array('groupId' => $visitGroup->groupId,
			'visitName'=>$visitName,
			'tableSpecific'=>$tableSpecificName,
			'order'=>intval($order),
			'localFormNeeded'=>intval($localFormNeed),
			'qcNeeded'=>intval($qcNeeded),
			'reviewNeeded'=>intval($reviewNeeded),
			'optional'=>intval($optional),
			'limitLowDays'=>intval($limitLowDays),
			'limitUpDays'=>intval($limitUpDays),
			'anonProfile'=>$anonProfile,
			'dicomConstraints'=>json_encode($dicomConstraints)
		));
        
		//Create specific table of the visit for form with relation with the review table
		$req=$linkpdo->prepare(' CREATE TABLE '.$tableSpecificName.' (id_review integer(11) NOT NULL, PRIMARY KEY (id_review));
            ALTER TABLE '.$tableSpecificName.' ADD FOREIGN KEY fk_idReview (id_review) REFERENCES reviews(id_review);
            ALTER TABLE '.$tableSpecificName.' ADD PRIMARY KEY (`id_review`); ');
        
		$req->execute();
	}
    
}