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
 * Review status progress determination
 */
class Study_Review_Manager
{

	private $studyObject;


	public function __construct(Study $studyObject)
	{
		$this->studyObject = $studyObject;
	}

	private function getAvailableReviewersName(): Array
	{
		//List the Reviewers declared in the study
		$reviewerUsersObjects = $this->studyObject->getUsersByRoleInStudy(User::REVIEWER);
		$availableReviewers = [];
		foreach ($reviewerUsersObjects as $reviewerObject) {
			$availableReviewers[] = $reviewerObject->lastName . " " . $reviewerObject->firstName;
		}

		return $availableReviewers;
	}

	/**
	 * List users who have done reviews (and other reviewers missing) for each visit
	 * with date of review, status of review, 
	 * @return array[]
	 */
	public function getReviewsDetailsByVisit() : Array
	{

		$availableReviewers = $this->getAvailableReviewersName();
		//Retrieve created Visit from the study Object
		$createdVisitObjects = $this->studyObject->getAllCreatedVisits();

		//GlobalMap
		$reviewdetailsMap = [];

		foreach ($createdVisitObjects as $createdVisit) {
			if ($createdVisit->stateQualityControl == Visit::QC_ACCEPTED) {
				//If QC Accepted, visit is suitable for review so analyze it
				$newVisit['visitId'] = $createdVisit->id_visit;
				$newVisit['visitModality'] = $createdVisit->visitGroupObject->groupModality;
				$newVisit['patientNumber'] = $createdVisit->patientCode;
				$newVisit['visit'] = $createdVisit->visitType;
				$newVisit['acquisitionDate'] = $createdVisit->acquisitionDate;
				$newVisit['reviewStatus'] = $createdVisit->reviewStatus;
				//Retrieve review
				try {
					$reviewObjects = $createdVisit->getReviewsObject(false);
				} catch (Exception $e) {
					$reviewObjects = [];
				}

				$newVisit['numberOfReview'] = count($reviewObjects);
				$newVisit['reviewDoneBy'] = [];
				$newVisit['reviewDetailsArray'] = [];
				foreach ($reviewObjects as $review) {
					$reviewerObject = $review->getUserObject();
					$details['user'] = $reviewerObject->lastName . " " . $reviewerObject->firstName;
					$details['date'] = $review->reviewDate;
					$newVisit['reviewDetailsArray'][] = $details;
					$newVisit['reviewDoneBy'][] = $reviewerObject->lastName . " " . $reviewerObject->firstName;
				}

				//Determine missing reviewer for this visit
				$newVisit['reviewNotDoneBy'] = array_diff($availableReviewers, $newVisit['reviewDoneBy']);

				//Add all data to the global map
				$reviewdetailsMap[$createdVisit->id_visit] = $newVisit;
			}
		}
		return $reviewdetailsMap;
	}
}
