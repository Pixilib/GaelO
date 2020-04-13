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
 * Collect patient's data from Orthanc Server
 * This class is not used anymore ...
 */

class Orthanc_Patient {
	public $patientName;
	public $patientID;
	public $patientSex;
	public $patientBirthDate;
	public $patientOrthancID;
	public $studiesOrthancID;
	public $studiesDetails;
	private $url;
	private $context;
	
	
	public function __construct($patientOrthancID, $url, $context) {
		//Set orthanc address
		$this->url=$url;
		$this->context=$context;
		//Set current patient Orthanc ID
		$this->patientOrthancID=$patientOrthancID;
	}
	
	/** 
	 * Get and store the main tags of the patient level
	 */
	public function retrievePatientData() {
		$context=stream_context_create($this->context);
		$json=file_get_contents($this->url.'/patients/'.$this->patientOrthancID, false, $context);
		$patientJson=json_decode($json, true);
		//Add needed informations in this object
		$this->patientName=$patientJson['MainDicomTags']['PatientName'];
		$this->patientID=$patientJson['MainDicomTags']['PatientID'];
		$this->patientSex=$patientJson['MainDicomTags']['PatientSex'];
		$this->patientBirthDate=$patientJson['MainDicomTags']['PatientBirthDate'];
		$this->studiesOrthancID=$patientJson['Studies'];
		$this->getStudiesData();
	}
	
	/**
	 * Retrieve data from study level
	 */
	public function getStudiesData() {
		foreach ($this->studiesOrthancID as $studyID) {
			//Create a study object of each study
			$study=new Orthanc_Study($studyID, $this->url, $this->context);
			//fetch the data of the study level
			$study->retrieveStudyData();
			//Add the study in the current object
			$this->studiesDetails[]=$study;
			
		}
	}
	
}
