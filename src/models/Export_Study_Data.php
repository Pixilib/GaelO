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
//SK faire evoluer vers generator pour eviter sortie memoire ?

/**
 * Export all data relative to a study
 */
class Export_Study_Data
{

	public $studyObject;
	private $allcreatedVisits;

	public function __construct(Study $studyObject)
	{

		$this->studyObject=$studyObject;

		$this->allcreatedVisits=[];

		$visitGroupArray=$this->studyObject->getAllPossibleVisitGroups();

		foreach ($visitGroupArray as $visitGroup) {

			try {
				$modalityCreatedVisits=$this->studyObject->getStudySpecificGroupManager($visitGroup->groupModality)->getCreatedVisits();
				$modalityDeletedVisits=$this->studyObject->getStudySpecificGroupManager($visitGroup->groupModality)->getCreatedVisits(true);
				array_push($this->allcreatedVisits, ...$modalityCreatedVisits, ...$modalityDeletedVisits);
			}catch (Exception $e) { }
		}
	}

	/**
	 * Generate and fill the patients CSV
	 */
	public function exportPatientTable(): String
	{

		$patientCsv[]=array('Patient Code', 'Initials', 'Gender', 'Birthdate', 'Registration Date', 'Investigator Name', 'Center Code', 'Center Name', 'Country', 'Withdraw', 'Withdraw Reason', 'Withdraw Date');

		$patientsInStudy=$this->studyObject->getAllPatientsInStudy();
		foreach ($patientsInStudy as $patient) {
			$patientCenter=$patient->getPatientCenter();
			$patientCsv[]=array(
				$patient->patientCode, $patient->patientLastName.$patient->patientFirstName, $patient->patientGender,
				$patient->patientBirthDate, $patient->patientRegistrationDate, $patient->patientInvestigatorName, $patientCenter->code, $patientCenter->name, $patientCenter->countryName,
				$patient->patientWithdraw, $patient->patientWithdrawReason, $patient->patientWithdrawDateString
			);
		}

		$patientCsvString=$this->writeCsv($patientCsv);

		return $patientCsvString;
	}


	/**
	 * 
	 */
	public function exportAssociatedFiles()
	{

		$zip=new ZipArchive;
		$tempZip=tempnam(ini_get('upload_tmp_dir'), 'TMPZIPAF_');
		$zip->open($tempZip, ZipArchive::CREATE);
        

		foreach ($this->allcreatedVisits as $visitObject) {
			$reviewsObjects=$this->getAllreviewObjects($visitObject);

			foreach ($reviewsObjects as $reviewObject) {

				foreach ($reviewObject->associatedFiles as $associatedFileKey => $associatedFilePath) {
					$associatedFileLocation = $reviewObject->getAssociatedFilePath($associatedFileKey);
					$zip->addFile($associatedFileLocation);

				};

			}

		}

		$zip->close();

		return $tempZip;

	}

	/**
	 * Export the visit table relative to this study
	 */
	public function exportVisitTable(): String
	{

		$visitCSV=[];

		//Prepare visit CSV
		$visitCSV[]=array(
			'Patient Code', 'Visit Group', 'ID Visit', 'Code Status', 'Creator Name', 'Creator Date',
			'Type', 'Status', 'Reason For Not Done', 'Acquisition Date', 'Upload Status', 'Uploader',
			'Upload Date', 'State Investigator Form', 'State QC', 'QC done by', 'QC date', 'Review Status', 'Review Date', 'Review Conclusion', 'visit deleted'
		);

		foreach ($this->allcreatedVisits as $visit) {
			$codeStatus=$this->dertermineVisitStatusCode($visit);
			$visitCSV[]=array(
				$visit->patientCode, $visit->visitGroupObject->groupModality, $visit->id_visit, $codeStatus, $visit->creatorName, $visit->creationDate,
				$visit->visitType, $visit->statusDone, $visit->reasonForNotDone, $visit->acquisitionDate, $visit->uploadStatus, $visit->uploaderUsername,
				$visit->uploadDate, $visit->stateInvestigatorForm, $visit->stateQualityControl, $visit->controllerUsername, $visit->controlDate,
				$visit->reviewStatus, $visit->reviewConclusionDate, $visit->reviewConclusion, $visit->deleted
			);
		}

		$visitCsvString=$this->writeCsv($visitCSV);

		return $visitCsvString;
	}

	/**
	 * Export Data relative to Imaging stored data (Orthanc Stored Data)
	 */
	public function getImagingData()
	{

		//Prepare Orthanc Series data CSV
		$orthancCSV[]=array(
			'ID Visit', 'Study Orthanc ID',
			'Study UID', 'Study Description', 'Dicom Patient Name', 'Dicom Patient ID', 'Serie Description', 'modality', 'Acquisition Date Time',
			'Serie Orthanc ID', 'Serie UID', 'Instance Number', 'Manufacturer', 'Disk Size', 'Serie Number', 'Patient Weight', 'Injected_Activity', 'Injected_Dose', 'Radiopharmaceutical', 'Half Life', 'Injected Time', 'Deleted'
		);

		$imagingVisit=array_filter($this->allcreatedVisits, function(Visit $visitObject) {
			$inArrayBool=in_array(
				$visitObject->visitGroupObject->groupModality,
				array(Visit_Group::GROUP_MODALITY_CT, Visit_Group::GROUP_MODALITY_PET, Visit_Group::GROUP_MODALITY_MR, Visit_Group::GROUP_MODALITY_RTSTRUCT)
			);
			return ($inArrayBool);
		});

		foreach ($imagingVisit as $visit) {

			$allSeries=$visit->getSeriesDetails();

			foreach ($allSeries as $serieObject) {
				$studyDetailsObject=$serieObject->studyDetailsObject;
				$orthancCSV[]=array(
					$studyDetailsObject->idVisit, $studyDetailsObject->studyOrthancId, $studyDetailsObject->studyUID,
					$studyDetailsObject->studyDescription, $studyDetailsObject->patientName, $studyDetailsObject->patientId, $serieObject->seriesDescription, $serieObject->modality, $serieObject->acquisitionDateTime, $serieObject->seriesOrthancID,
					$serieObject->serieUID, $serieObject->numberInstances, $serieObject->manufacturer, $serieObject->serieUncompressedDiskSize, $serieObject->seriesNumber, $serieObject->patientWeight, $serieObject->injectedActivity, $serieObject->injectedDose, $serieObject->radiopharmaceutical, $serieObject->halfLife, $serieObject->injectedDateTime, $serieObject->deleted
				);
			}
		}

		$orthancCsvFile=$this->writeCsv($orthancCSV);

		return $orthancCsvFile;
	}

	/**
	 * Return an associative array in which each CSV file reference will be stored
	 */
	public function getReviewData()
	{

		$mappedVisitByGroup=[];

		foreach ($this->allcreatedVisits as $visitObject) {
			$modality=$visitObject->visitGroupObject->groupModality;
			$visitName=$visitObject->visitType;
			$mappedVisitByGroup[$modality][$visitName][]=$visitObject;
		};

		foreach ($mappedVisitByGroup as $modality => $visitTypes) {

			$groupObject=$this->studyObject->getSpecificGroup($modality);

			foreach ($visitTypes as $visitType => $visitArray) {
				$csv=[];

				//Export Reviews
				$genericHeader=array('Patient Code', 'Visit Type', 'ID Visit', 'ID review', 'Reviewer', 'Review Date', 'Validated', 'Local Form', 'Adjudcation form', 'Review Deleted');

				$visitTypeObject=$groupObject->getVisitType($visitType);
				$specificFormTable=$visitTypeObject->getSpecificFormColumn();
				unset($specificFormTable[0]);

				$csv[]=array_merge($genericHeader, $specificFormTable);

				foreach ($visitArray as $visitObject) {

					array_push($csv, ...$this->getReviews($visitObject));
				}

				$reviewCsvFiles[$modality.'_'.$visitType]=$this->writeCsv($csv);
			}
		}

		return $reviewCsvFiles;
	}

	private function getAllreviewObjects(Visit $visitObject) : array {

		$localReviews=[];
		try {
			$localReviews[]=$visitObject->getReviewsObject(true);
		}catch (Exception $e) { }

		$expertReviews=[];
		try {
			$expertReviews=$visitObject->getReviewsObject(false, true);
		}catch (Exception $e) { }

		//Merge all reviews in an array
		$reviewObjects=array_merge($localReviews, $expertReviews);

		return $reviewObjects;

	}

	/**
	 * Merge local and review form and call getReviewDatas function to build one line of the CSV array for each review
	 */
	private function getReviews(Visit $visitObject): array
	{

		$reviewObjects=$this->getAllreviewObjects($visitObject);

		$csv=[];
		foreach ($reviewObjects as $reviewObject) {
			$patientCode=$visitObject->patientCode;
			$visitType=$visitObject->visitType;
			$reviewData=$this->getReviewDatas($reviewObject);
			$csv[]=[$patientCode, $visitType, ...$reviewData];
		}

		return $csv;
	}

	/**
	 * Merge generic and specific column to build a CSV array
	 */
	private function getReviewDatas(Review $review): array
	{
		//Add to final map
		$reviewDatas=$this->getGenericData($review);
		$specificData=$review->getSpecificData();
		unset($specificData["id_review"]);
		$specificDataArray = [];
		if( !empty($specificData) ){
			$specificDataArray = array_values($specificData);
		}
		$reviewLine=[...$reviewDatas, ...$specificDataArray];

		return $reviewLine;
	}

	/**
	 * Build the generic part of the review array
	 */
	private function getGenericData(Review $review) : array
	{
		//Add to final map
		$reviewDatas=array(
			$review->id_visit, $review->id_review,
			$review->username, $review->reviewDate, $review->validated, $review->isLocal, $review->isAdjudication, $review->deleted
		);

		return $reviewDatas;
	}

	/**
	 * Write CSV Array to a temporary file
	 */
	private function writeCsv($csvArray) : String
	{

		$tempCsv=tempnam(ini_get('upload_tmp_dir'), 'TMPCSV_');
		$fichier_csv=fopen($tempCsv, 'w');
		foreach ($csvArray as $fields) {
			fputcsv($fichier_csv, $fields);
		}
		fclose($fichier_csv);

		return $tempCsv;
	}


	/**
	 * Return Code Status
	 * 0 Visit not Done
	 * 1 Done but DICOM and Form not sent
	 * 2 Done but upload not done (form sent)
	 * 3 done but investigator form not done (dicom sent)
	 * 4 QC not done
	 * 5 QC corrective action
	 * 6 QC refused
	 * 7 Review Not Done
	 * 8 Review ongoing
	 * 9 Review Wait adjudication
	 * 10 review done
	 * -1 If any of these case (should not happen)
	 * @param Visit $visitObject
	 * @return number
	 */
	private function dertermineVisitStatusCode(Visit $visitObject) : int
	{

		if ($visitObject->statusDone == Visit::NOT_DONE) {
			return 0;
		}else if ($visitObject->uploadStatus == Visit::NOT_DONE || $visitObject->stateInvestigatorForm == Visit::NOT_DONE) {
			if ($visitObject->uploadStatus == Visit::NOT_DONE && $visitObject->stateInvestigatorForm == Visit::NOT_DONE) {
				return 1;
			}else if ($visitObject->stateInvestigatorForm == Visit::NOT_DONE) {
				return 3;
			}else if ($visitObject->uploadStatus == Visit::NOT_DONE) {
				return 2;
			}
		}else if ($visitObject->qcStatus == Visit::QC_NOT_DONE) {
			return 4;
		}else if ($visitObject->qcStatus == Visit::QC_CORRECTIVE_ACTION_ASKED) {
			return 5;
		}else if ($visitObject->qcStatus == Visit::QC_REFUSED) {
			return 6;
		}else if ($visitObject->reviewStatus == Visit::NOT_DONE) {
			return 7;
		}else if ($visitObject->reviewStatus == Visit::REVIEW_ONGOING) {
			return 8;
		}else if ($visitObject->reviewStatus == Visit::REVIEW_WAIT_ADJUDICATION) {
			return 9;
		}else if ($visitObject->reviewStatus == Visit::REVIEW_DONE) {
			return 10;
		}else {
			//If none of these case return -1, should not happen
			return -1;
		}
	}

}
