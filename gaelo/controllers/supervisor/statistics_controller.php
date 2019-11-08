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
 * Display the review manager, tools to follow review statistics progress
 */

Session::checkSession();
$linkpdo = Session::getLinkpdo();

$userObject = new User($_SESSION['username'], $linkpdo);
$accessCheck = $userObject->isRoleAllowed($_SESSION['study'], $_SESSION['role']);

if ($accessCheck && $_SESSION['role'] == User::SUPERVISOR) {

	if (!isset($_POST['chartId'])) {
		die('chartId is not set.');
	}

	$studyObj = new Study($_SESSION['study'], $linkpdo);
	$statisticsObj = $studyObj->getStatistics();

	switch ($_POST['chartId']) {
		case 'acquPETDelay':
			$chartData = json_encode($studyObj->getStatistics()->getAcquisitionPetDelay(), JSON_NUMERIC_CHECK);
			require 'views/supervisor/statistics/statistics_acqu_pet_delay.php';
			break;

		case 'studyProgress':
			$chartData = json_encode($studyObj->getStatistics()->getUploadFractionAndDelay(), JSON_NUMERIC_CHECK);
			require 'views/supervisor/statistics/statistics_study_progress.php';
			break;

		case 'reviewCount':
			$chartData = json_encode($studyObj->getStatistics()->getReviewsDate(), JSON_NUMERIC_CHECK);
			require 'views/supervisor/statistics/statistics_review_count.php';
			break;

		case 'reviewData':
			$chartData = json_encode($studyObj->getStatistics()->getReviewData(false), JSON_NUMERIC_CHECK);
			require 'views/supervisor/statistics/statistics_review_data.php';
			break;

		case 'reviewStatus':
			$chartData = json_encode($studyObj->getStatistics()->getReviewStatus(), JSON_NUMERIC_CHECK);
			require 'views/supervisor/statistics/statistics_review_status.php';
			break;

		case 'QCStatus':
			$chartData = json_encode($studyObj->getStatistics()->getQcStatus(), JSON_NUMERIC_CHECK);
			require 'views/supervisor/statistics/statistics_qc_status.php';
			break;

		case 'QCTime':
			$chartData = json_encode($studyObj->getStatistics()->getQCTime(), JSON_NUMERIC_CHECK);
			require 'views/supervisor/statistics/statistics_qc_time.php';
			break;

		case 'conclusionTime':
			$chartData = json_encode($studyObj->getStatistics()->getConclusionTime(), JSON_NUMERIC_CHECK);
			require 'views/supervisor/statistics/statistics_conclusion_time.php';
			break;

		default:
			die('Unknown chartId.');
	}
} else {
	require 'includes/no_access.php';
}
