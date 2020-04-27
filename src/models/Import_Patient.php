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
 * Import patient in study (import data from Json structured file)
 */

class Import_Patient {

	private $originalJson;
	private $linkpdo;
	public $sucessList;
	public $failList;
	private $study;
	private $studyObject;
	
	public function __construct($originalJson, $study, $linkpdo) {
		$this->linkpdo=$linkpdo;
		//Store the JSON file and the target study
		$this->originalJson=$originalJson;
		$this->study=$study;
		$this->studyObject=new Study($study, $linkpdo);
	}

	public function readJson() {
		$jsonImport=json_decode($this->originalJson, true);
		
		//For each patient from the array list
		foreach ($jsonImport as $patient) {
			//Get patient info
			$patientNumber=$patient['patientNumber'];
			$patientLastName=$patient['lastName'];
			$patientFirstName=$patient['firstName'];
			$patientGender=$patient['gender'];
			$patientInvestigatorNumCenter=$patient['investigatorNumCenter'];
			$patientDateOfBirth=$patient['dateOfBirth'];
			$patientInvestigatorName=$patient['investigatorName'];
			$patientRegistrationDate=$this->parseRegistrationDate($patient['registrationDate']);
		    
			//Check condition before import
			$isNewPatient=$this->isNewPatient($patientNumber);
			$isCorrectPatientNumberLenght=$this->isCorrectPatientNumberLenght($patientNumber);
			$isExistingCenter=$this->isExistingCenter($patientInvestigatorNumCenter);
			$isPrefixCorrect=$this->isCorrectPrefix($patientNumber);
			
			if ($isNewPatient && $isCorrectPatientNumberLenght && $isPrefixCorrect && $isExistingCenter && !empty($patientRegistrationDate)) {
				//Store in DB
				$birthDateArray=explode("/", $patientDateOfBirth);
				if (GAELO_DATE_FORMAT == 'm.d.Y') {
					$birthDay=intval($birthDateArray[1]);
					$birthMonth=intval($birthDateArray[0]);
				}else if (GAELO_DATE_FORMAT == 'd.m.Y') {
					$birthDay=intval($birthDateArray[0]);
					$birthMonth=intval($birthDateArray[1]);
				}
				$birthYear=intval($birthDateArray[2]);
			    
				$insertddb=$this->addPatientToDatabase($patientNumber, $patientLastName, $patientFirstName, $patientGender,
					$patientInvestigatorNumCenter, $patientRegistrationDate, $birthDay, $birthMonth, $birthYear, $patientInvestigatorName);
				
				//Store the patient result import process in this object
				if ($insertddb) {
						$this->sucessList[]=$patientNumber;
				}else {
					$patientFailed['PatientNumber']=$patientNumber;
					$patientFailed['Reason']="Can't write to DB, wrong date or other wrong input";
					$this->failList[]=$patientFailed;
				}

			//If conditions not met, add to the fail list with the respective error reason
			}else {
			    
				if (!$isExistingCenter) {
					if (empty($patientInvestigatorNumCenter)) {
						$this->failList['Missing Num Center'][]=$patientNumber;
					}else {
						$this->failList['Unknown Center'][]=$patientNumber;
					}

				}else if (!$isCorrectPatientNumberLenght) {
					$this->failList['Wrong PatientNumber length'][]=$patientNumber;
				    
				}else if (!$isNewPatient) {
					$this->failList['Patient already in Database'][]=$patientNumber;
				    
				}else if (empty($patientRegistrationDate)) {
					$this->failList['Empty Registration Date'][]=$patientNumber;
				}else if (!$isPrefixCorrect) {
					$this->failList['Wrong Patient Code Prefix'][]=$patientNumber;
				}
				
				
			}

		}

	}
	
	/**
	 * Parse registration date according to plateform preference (french or US format)
	 * @param string $registrationDate
	 * @return NULL|DateTime
	 */
	private function parseRegistrationDate(?string $registrationDate) {
		$dateNbArray=explode('/', $registrationDate);
	    
		if (GAELO_DATE_FORMAT == 'm.d.Y') {
			$registrationDay=intval($dateNbArray[1]);
			$registrationMonth=intval($dateNbArray[0]);
		}else if (GAELO_DATE_FORMAT == 'd.m.Y') {
			$registrationDay=intval($dateNbArray[0]);
			$registrationMonth=intval($dateNbArray[1]);
		}
	    
		$registrationYear=intval($dateNbArray[2]);
	    
		if ($registrationDay == 0 || $registrationMonth == 0 || $registrationYear == 0) {
			return null;
		}
	    
		try {
			$dateResult=new DateTime($registrationYear.'-'.$registrationMonth.'-'.$registrationDay);
		}catch (Exception $e) {
			return null;
		}
		return $dateResult;
	   
	}

	/**
	 * Check that the importing patient is not already known in the system
	 * NB : Each patient code should be unique (across study), patient number should include a study identifier
	 * @param $patientCode
	 * @return boolean
	 */
	private function isNewPatient($patientCode) {
		try {
			new Patient($patientCode, $this->linkpdo);
		}catch (Exception $e1) {
			return true;
		}
	    
		return false;
	}

	/**
	 * Check that patient number has the correct lenght
	 * @param $patientNumber
	 * @return boolean
	 */
	private function isCorrectPatientNumberLenght($patientNumber) {
		$lenghtImport=strlen($patientNumber);
		
		if ($lenghtImport == GAELO_PATIENT_CODE_LENGHT) {
			return true;
		}else {
			return false;
		}
	}

	private function isCorrectPrefix($patientNumber) {
		//If no prefix return true
		if (empty($this->studyObject->patientCodePrefix)) {
			return true;
		}
		//test that patient code start with study prefix
		$patientNumberString=strval($patientNumber);
		$studyPrefixString=strval($this->studyObject->patientCodePrefix);
		return $this->startsWith($patientNumberString, $studyPrefixString);

	}

	private function startsWith(string $string, string $startString) { 
		$len=strlen($startString); 
		return (substr($string, 0, $len) === $startString); 
	} 

	/**
	 * Check that patient's center is one of known center in the plateform
	 * @param $patientNumCenter
	 * @return boolean
	 */
	private function isExistingCenter($patientNumCenter) {
		if (is_null($patientNumCenter) || strlen($patientNumCenter) == 0) {
			return false;
		}
	    
		try {
			new Center($this->linkpdo, $patientNumCenter);
		}catch (Exception $e1) {
			return false;
		}
	    
		return true;
	}

	/**
	 * Write the patient in the database
	 * @param  $patientNumber
	 * @param  $patientLastName
	 * @param  $patientFirstName
	 * @param  $patientGender
	 * @param  $patientInvestigatorCenter
	 * @param  $patientInvestigatorNumCenter
	 * @param  $dateRegistration
	 * @param  $patientDateOfBirth
	 * @param  $patientInvestigatorName
	 * @return boolean
	 */
	private function addPatientToDatabase($patientNumber, string $patientLastName, string $patientFirstName, string $patientGender
		, $patientInvestigatorNumCenter, $dateRegistration, $patientBirthDay, $patientBirthMonth, $patientBirthYear, string $patientInvestigatorName) {
		
		try {
			$insert_bdd=$this->linkpdo->prepare('INSERT INTO patients(study, code, first_name, last_name, gender, birth_day, birth_month, birth_year, registration_date, investigator_name, center)
			VALUES(:study, :code, :first_name, :last_name, :gender, :birth_day, :birth_month, :birth_year, :registration_date, :investigator_name, :center)');
			
			$insert_bdd->execute(array('code' => $patientNumber,
													'first_name' => @strtoupper($patientFirstName[0]),
													'last_name' => @strtoupper($patientLastName[0]),
													'gender' => @strtoupper($patientGender[0]),
													'birth_day' => $patientBirthDay,
													'birth_month' => $patientBirthMonth,
													'birth_year' => $patientBirthYear,
													'registration_date' => $dateRegistration->format('Y-m-d'),
													'investigator_name' => $patientInvestigatorName,
													'center' => $patientInvestigatorNumCenter,
													'study' => $this->study));
			$success=true;
		}catch (Exception $e) {
			$success=false;
		}

		return $success;

	}
	
	public function getHTMLImportAnswer() {
		return $this->buildSuccessAnswer().$this->buildErrorAnswer();
	}
	
	public function getTextImportAnswer() {
		//Prepare Html2PlainText for email validity (both version to enhance spam validation)
		$htmlMessageObject=new \Html2Text\Html2Text($this->getHTMLImportAnswer());
		return $htmlMessageObject->getText();
	    
	}
	
	/**
	 * Build HTML for error answer
	 * @return string
	 */
	private function buildErrorAnswer() {
		//List of failed patients
		$failReport='Failed Patients: <br>';
		if (!empty($this->failList)) {
			foreach ($this->failList as $key=>$value) {
				if (!empty($value)) {
					$failReport=$failReport.$key.':<br>';
					if (is_array($value)) {
						$failReport=$failReport.implode('<br>', $value).'<br>';
					}else {
						$failReport=$failReport.$value.'<br>';
					}
				}
			}
		}else {
			$failReport=$failReport.' None <br>';
		}
	    
		return $failReport;
	}
	
	/**
	 * Generate HTML for sucess answer
	 * @return string
	 */
	private function buildSuccessAnswer() {
		//List of succeded patients
		$successReport='Success Patients: <br>';
		if (!empty($this->sucessList)) {
			foreach ($this->sucessList as $value) {
				$success=$value;
				$successReport=$successReport.$success.'<br>';
			}
		}else {
			$successReport=$successReport.' None <br>';
		}
	    
		return $successReport;
	}

}
