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
 * Acess series data in database
 */
Class Series_Details {
    
	private $linkpdo;
	public $seriesOrthancID;
	public $studyOrthancId;
	public $seriesNumber;
	public $manufacturer;
	public $modelName;
	public $seriesDescription;
	public $modality;
	public $acquisitionDateTime;
	public $injectedDose;
	public $radiopharmaceutical;
	public $injectedDateTime;
	public $injectedTime;
	public $injectedActivity;
	public $halfLife;
	public $patientWeight;
	public $numberInstances;
	public $serieUID;
	public $deleted;
	public $serieDiskSize;
	public $serieUncompressedDiskSize;
    
	public $parentIdVisit;
    
	public $studyDetailsObject;

	public function __construct(String $seriesOrthancID, PDO $linkpdo) {
		$this->linkpdo=$linkpdo;
		$this->seriesOrthancID=$seriesOrthancID;
        
		$seriesQuery=$this->linkpdo->prepare('SELECT * FROM orthanc_series
                                                WHERE series_orthanc_id=:seriesOrthancID' );
        
		$seriesQuery->execute(array(
			"seriesOrthancID" => $this->seriesOrthancID
		));
        
		$serieData=$seriesQuery->fetch(PDO::FETCH_ASSOC);
        
		$this->studyOrthancId=$serieData['study_orthanc_id'];
		$this->seriesNumber=$serieData['serie_number'];
		$this->manufacturer=$serieData['manufacturer'];
		$this->modelName=$serieData['model_name'];
		$this->seriesDescription=$serieData['series_description'];
		$this->modality=$serieData['modality'];
		$this->acquisitionDateTime=$serieData['acquisition_datetime'];
		$this->injectedDose=$serieData['injected_dose'];
		$this->radiopharmaceutical=$serieData['radiopharmaceutical'];
		$this->injectedDateTime=$serieData['injected_datetime'];
		$this->injectedTime=$serieData['injected_time'];
		$this->injectedActivity=$serieData['injected_activity'];
		$this->halfLife=$serieData['half_life'];
		$this->patientWeight=$serieData['patient_weight'];
		$this->numberInstances=$serieData['number_of_instances'];
		$this->serieUID=$serieData['serie_uid'];
		$this->deleted=$serieData['deleted'];
		$this->serieDiskSize=$serieData['serie_disk_size'];
		$this->serieUncompressedDiskSize=$serieData['serie_uncompressed_disk_size'];
        
		//Store study related data
		$this->studyDetailsObject=new Study_Details($this->studyOrthancId, $linkpdo);
        
		//Store ID_Visit related to this dicom series
		$this->parentIdVisit=$this->studyDetailsObject->idVisit;

        
        
	}
    
	/**
	 * Modify deleted status of the serie
	 * @param bool $deleted
	 */
	public function changeDeletionStatus(bool $deleted) {
		$connecter=$this->linkpdo->prepare('UPDATE orthanc_series SET deleted=:deleted WHERE series_orthanc_id = :seriesOrthancID');
		$connecter->execute(array(
			"seriesOrthancID" => $this->seriesOrthancID,
			"deleted"=>intval($deleted)
		));
    	
	}
    
    
	/**
	 * Instanciate Series object by Series Instance UID
	 * @param string $uid
	 * @param PDO $linkpdo
	 * @return Series_Details
	 */
	public static function getSerieObjectByUID(string $uid, PDO $linkpdo) {
        
		$seriesQuery=$linkpdo->prepare('SELECT series_orthanc_id FROM orthanc_series
                                                WHERE serie_uid=:seriesUID' );
        
		$seriesQuery->execute(array(
			"seriesUID" => $uid
		));
        
		$serieOrthancID=$seriesQuery->fetch(PDO::FETCH_COLUMN);
        
		return new Series_Details($serieOrthancID, $linkpdo);
        
	}
}