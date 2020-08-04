<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

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
 * Access data of a dicom study in database
 */

Class StudyDetail extends Model {
	private $linkpdo;
	public $studyUID;
	public $idVisit;
	public $uploaderUsername;
	public $uploadDate;
    
	public $studyAcquisitionDate;
	public $studyAcquisitionTime;
	public $studyAcquisitionDateTime;
	public $studyOrthancId;
	public $studyDescription;
	public $patientOrthancId;
	public $patientName;
	public $patientId;
    
	public $nbOfSeries;
	public $diskSize;
	public $uncompressedDiskSize;
	public $deleted;
	public $numberOfInstances;
    
	public static function getStudyObjectByUID(string $studyUID, PDO $linkpdo) {
        
		$studyQuery=$linkpdo->prepare('SELECT study_orthanc_id FROM orthanc_studies
                                                WHERE study_uid=:studyUID' );
        
		$studyQuery->execute(array(
			"studyUID" => $studyUID
		));
        
		$orthancID=$studyQuery->fetch(PDO::FETCH_COLUMN);
        
		return new Study_Details($orthancID, $linkpdo);
        
	}
    
	public function __construct($studyOrthancId, $linkpdo) {
		$this->linkpdo=$linkpdo;
		$this->studyOrthancId=$studyOrthancId;
        
		$studyQuery=$this->linkpdo->prepare('SELECT * FROM orthanc_studies
                                                WHERE study_orthanc_id=:studyOrthancID' );
        
		$studyQuery->execute(array(
			"studyOrthancID" => $this->studyOrthancId
		));
        
		$studyData=$studyQuery->fetch(PDO::FETCH_ASSOC);
        
		$this->studyAcquisitionDate=$studyData['acquisition_date'];
		$this->studyAcquisitionTime=$studyData['acquisition_time'];
		$this->studyAcquisitionDateTime=$studyData['acquisition_datetime'];
		$this->studyUID=$studyData['study_uid'];
		$this->studyDescription=$studyData['study_description'];
		$this->patientOrthancId=$studyData['patient_orthanc_id'];
		$this->patientName=$studyData['patient_name'];
		$this->patientId=$studyData['patient_id'];
		$this->nbOfSeries=$studyData['number_of_series'];
		$this->diskSize=$studyData['disk_size'];
		$this->uncompressedDiskSize=$studyData['uncompressed_disk_size'];
		$this->numberOfInstances=$studyData['number_of_instances'];
		$this->deleted=$studyData['deleted'];
		$this->idVisit=$studyData['id_visit'];
        
		$this->uploaderUsername=$studyData['uploader'];
		$this->uploadDate=$studyData['upload_date'];
        
	}
    
	public function getChildSeries() {
        
		$idFetcher=$this->linkpdo->prepare("SELECT orthanc_series.series_orthanc_id FROM orthanc_series, orthanc_studies
										WHERE orthanc_series.study_orthanc_id=orthanc_studies.study_orthanc_id
                                        AND orthanc_studies.study_orthanc_id=:studyOrthancID");
        
		$idFetcher->execute(array(
			"studyOrthancID" => $this->studyOrthancId
		));
        
		$orthancSeriesIDs=$idFetcher->fetchAll(PDO::FETCH_COLUMN);
        
		$childSeriesObject=[];
		foreach ($orthancSeriesIDs as $orthancSerieID) {
			$childSeriesObject[]=new Series_Details($orthancSerieID, $this->linkpdo);
		}
        
		return $childSeriesObject;
        
	}
    
	public function changeDeletionStatus($deleted) {
        
		//Activate only if no other activated study
		if ($deleted == false && $this->isExistingActivatedStudyForVisit() == true) {
			throw new Exception("already existing activated study");
		}
        
		$changeStatusUpload=$this->linkpdo->prepare('UPDATE orthanc_studies SET deleted = :deleted WHERE id_visit = :idvisit AND study_orthanc_id=:studyOrthancID');
		$changeStatusUpload->execute(array('idvisit'=> $this->idVisit,
			'studyOrthancID'=>$this->studyOrthancId, 'deleted'=>intval($deleted)));
        
		//reactivate all series of this study
		$childSeries=$this->getChildSeries();
		foreach ($childSeries as $serie) {
			$serie->changeDeletionStatus(false);
		}
	}
    
	private function isExistingActivatedStudyForVisit() {
        
		$studyQuery=$this->linkpdo->prepare('SELECT study_orthanc_id FROM orthanc_studies
                                        WHERE orthanc_studies.id_visit=:idVisit AND deleted=0;
                                    ');
		$studyQuery->execute(array('idVisit' => $this->idVisit));
        
		$dataStudies=$studyQuery->fetchAll(PDO::FETCH_COLUMN);
        
		if (empty($dataStudies)) {
			return false;
		} else {
			return true;
		}
        
	}
}