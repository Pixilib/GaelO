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

namespace App\GaelO\Services\StoreObjects;

use App\GaelO\Constants\Constants;
use App\GaelO\Services\OrthancService;

/**
 * Collect patient's data from Orthanc Server
 * This class is not used anymore ...
 */

class OrthancPatient {
	private OrthancService $orthancService;
    public string $patientOrthancID;

	public ?string $patientName;
	public ?string $patientID;
	public ?string $patientSex;
	public ?string $patientBirthDate;
	public array $studiesOrthancID = [];
	public array $studiesDetails = [];


    public function __construct(OrthancService $orthancService) {
		$this->orthancService=$orthancService;
	}

	public function setPatientID(string $patientOrthancID) {
		//Set current patient Orthanc ID
		$this->patientOrthancID=$patientOrthancID;
	}

	/**
	 * Get and store the main tags of the patient level
	 */
	public function retrievePatientData() {
        $patientDetails=$this->orthancService->getOrthancRessourcesDetails(Constants::ORTHANC_PATIENTS_LEVEL, $this->patientOrthancID);
		//Add needed informations in this object
		$this->patientName=$patientDetails['MainDicomTags']['PatientName'];
		$this->patientID=$patientDetails['MainDicomTags']['PatientID'];
		$this->patientSex=$patientDetails['MainDicomTags']['PatientSex'];
		$this->patientBirthDate=$patientDetails['MainDicomTags']['PatientBirthDate'];
		$this->studiesOrthancID=$patientDetails['Studies'];
		$this->getStudiesData();
	}

	/**
	 * Retrieve data from study level
	 */
	public function getStudiesData() {
		foreach ($this->studiesOrthancID as $studyID) {
			//Create a study object of each study
            $study=new OrthancStudy($this->orthancService);
            $study->setStudyOrthancID($studyID);
			//fetch the data of the study level
			$study->retrieveStudyData();
			//Add the study in the current object
			$this->studiesDetails[]=$study;

		}
	}

}
