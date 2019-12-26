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
 * Export database data relative to current study
 */

header( 'content-type: text/html; charset=utf-8' );
require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

Session::checkSession();
$linkpdo=Session::getLinkpdo();

@Session::logInfo('Username : '.$_SESSION['username'].
    ' Role: '.$_SESSION ['role'].' Study: '.$_SESSION['study']);


$userObject=new User($_SESSION['username'], $linkpdo);
$accessCheck=$userObject->isRoleAllowed($_SESSION['study'], $_SESSION['role']);

if ($accessCheck && $_SESSION['role'] == User::SUPERVISOR ) {
    $studyObject=new Study($_SESSION['study'], $linkpdo);
    
    //Prepare patient CSV
    $patientCsv[]=array('Patient Code','Initials', 'Gender','Birthdate','Registration Date','Investigator Name', 'Center Code', 'Center Name', 'Country', 'Withdraw', 'Withdraw Reason', 'Withdraw Date');
    
    $patientsInStudy=$studyObject->getAllPatientsInStudy();
    foreach ($patientsInStudy as $patient){
        $patientCenter=$patient->getPatientCenter();
        $patientCsv[]=array ($patient->patientCode, $patient->patientLastName.$patient->patientFirstName, $patient->patientGender,
            $patient->patientBirthDate, $patient->patientRegistrationDate, $patient->patientInvestigatorName, $patientCenter->code, $patientCenter->name, $patientCenter->countryName,
            $patient->patientWithdraw,$patient->patientWithdrawReason, $patient->patientWithdrawDateString );
    }

    $patientCsvFile=writeCsv($patientCsv);
    
    //Prepare visit CSV
    $visitCSV[]=array('Patient Code', 'ID Visit','Code Status', 'Creator Name', 'Creator Date',
        'Type', 'Status', 'Reason For Not Done','Acquisition Date', 'Upload Status', 'Uploader', 
        'Upload Date', 'State Investigator Form', 'State QC', 'QC done by', 'QC date', 'Review Status', 'Review Date','Review Conclusion', 'visit deleted');
    
    $allcreatedVisits=$studyObject->getCreatedVisits();
    
    foreach ($allcreatedVisits as $visit) {
    	$codeStatus=dertermineVisitStatusCode($visit);
    	$visitCSV[]=array ($visit->patientCode, $visit->id_visit, $codeStatus, $visit->creatorName, $visit->creationDate,
            $visit->visitType, $visit->statusDone, $visit->reasonForNotDone, $visit->acquisitionDate, $visit->uploadStatus, $visit->uploaderUsername,
            $visit->uploadDate,$visit->stateInvestigatorForm, $visit->stateQualityControl, $visit->controllerUsername, $visit->controlDate,
        		$visit->reviewStatus,$visit->reviewConclusionDate,$visit->reviewConclusion, $visit->deleted );
    }
    $visitCsvFile=writeCsv($visitCSV);
    
    //Prepare Orthanc Series data CSV
    $orthancCSV=[];
    $orthancCSV[]=array('ID Visit', 'Study Orthanc ID',
        'Study UID', 'Study Description', 'Dicom Patient Name', 'Dicom Patient ID', 'Serie Description', 'modality', 'Acquisition Date Time',
        'Serie Orthanc ID', 'Serie UID', 'Instance Number', 'Manufacturer', 'Disk Size', 'Serie Number', 'Patient Weight', 'Injected_Activity', 'Injected_Dose', 'Radiopharmaceutical', 'Half Life', 'Injected Time', 'Deleted');
    
    foreach ($allcreatedVisits as $visit) {

        $allSeries=$visit->getSeriesDetails();
        
        foreach ($allSeries as $serieObject){
            $studyDetailsObject=$serieObject->studyDetailsObject;
            $orthancCSV[]=array ($studyDetailsObject->idVisit, $studyDetailsObject->studyOrthancId, $studyDetailsObject->studyUID,
            		$studyDetailsObject->studyDescription, $studyDetailsObject->patientName, $studyDetailsObject->patientId, $serieObject->seriesDescription, $serieObject->modality, $serieObject->acquisitionDateTime, $serieObject->seriesOrthancID,
                $serieObject->serieUID,$serieObject->numberInstances, $serieObject->manufacturer, $serieObject->serieUncompressedDiskSize, $serieObject->seriesNumber, $serieObject->patientWeight, $serieObject->injectedActivity, $serieObject->injectedDose, $serieObject->radiopharmaceutical, $serieObject->halfLife, $serieObject->injectedDateTime, $serieObject->deleted );
            
        }

    }
    
    $orthancCsvFile=writeCsv($orthancCSV);

    //Export Reviews
    $genericHeader=array('ID Visit', 'ID review', 'Reviewer','Review Date','Validated','Local Form','Adjudcation_form', 'Review Deleted');
    //Add specific header for each visit Type
    $visitsTypeAnswer= $studyObject->getAllPossibleVisitTypes();
    $specificColumn=[];
    //Prepare Review CSV
    $reviewCSV=[];
    
    //Generate title with specific column name
    foreach ($visitsTypeAnswer as $visitType){
        $reviewCSV[$visitType->name]=[];
        $specificFormTable=$visitType->getSpecificFormColumn();
        unset($specificFormTable[0]);
        $specificColumn[$visitType->name]=$specificFormTable;
        $reviewCSV[$visitType->name][]=array_merge($genericHeader, $specificColumn[$visitType->name]);
        
    }

    foreach ($allcreatedVisits as $visit) {
        
        $localReviews=$visit->getReviewsObject(true);
        $expertReviews=$visit->getReviewsObject(false);
        
        //Merge all reviews in an array
        $reviews=[];
        if(!empty($localReviews)){
            $reviews[]=$localReviews;
        }
        foreach ($expertReviews as $expertReview){
            $reviews[]=$expertReview;
        }
        
        foreach ($reviews as $review){
            //Add to final map
            $reviewDatas=array($review->id_visit, $review->id_review,
                $review->username, $review->reviewDate, $review->validated, $review->isLocal,$review->isAdjudication, $review->deleted);
            
            $specificData=$review->getSpecificData();
            foreach ($specificColumn[$visit->visitType] as $key){
                if($key=="id_review") continue;
                $reviewDatas[]=$specificData[$key];
            }
            
            $reviewCSV[$visit->visitType][]=$reviewDatas;
            
        }
    }
    
    //For each Visit create a CSV file in a key array ordered by visit
    $ReivewCsvFiles=[];
    foreach ($visitsTypeAnswer as $visit){
        $ReivewCsvFiles[$visit->name]=writeCsv($reviewCSV[$visit->name]);
    }
   
    //Output everything for download
    $date =Date('Ymd_his');
    header('Content-type: application/zip');
    header('Content-Disposition: attachment; filename="export_study_'.$_SESSION['study'].'_'.$date.'.zip"');
    
    //Final ZIP creation
    $zip = new ZipArchive;
    $tempZip = tempnam(ini_get('upload_tmp_dir'), 'TMPZIP_');
    $zip->open($tempZip, ZipArchive::CREATE);
    $zip->addFile($patientCsvFile, "export_patient.csv");
    $zip->addFile($visitCsvFile, "export_visits.csv");
    $zip->addFile($orthancCsvFile, "export_orthanc.csv");
    foreach ($ReivewCsvFiles as $key=>$file){
        $zip->addFile($file, "export_review_$key.csv");
    }
    $zip->close();
    
    
    readfile($tempZip);
    
    //Delete Temp Files
    unlink($patientCsvFile);
    unlink($visitCsvFile);
    unlink($orthancCsvFile);
    unlink($tempZip);
    foreach ($ReivewCsvFiles as $key=>$file){
        unlink($file);
    }
    
    
    
}else {
    header('HTTP/1.0 403 Forbidden');
    die('You are not allowed to access this file.');
}

function writeCsv($csvArray){
    
    $tempCsv = tempnam(ini_get('upload_tmp_dir'), 'TMPCSV_');
    $fichier_csv = fopen($tempCsv, 'w');
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
 * @param Visit $visitObject
 * @return number
 */
function dertermineVisitStatusCode(Visit $visitObject){
	
	if($visitObject->statusDone==Visit::NOT_DONE){
		return 0;
	}else if($visitObject->uploadStatus==Visit::NOT_DONE || $visitObject->stateInvestigatorForm==Visit::NOT_DONE){
		if($visitObject->uploadStatus==Visit::NOT_DONE && $visitObject->stateInvestigatorForm==Visit::NOT_DONE){
			return 1;
		}
		else if($visitObject->stateInvestigatorForm==Visit::NOT_DONE){
			return 3;
		} 
		else if($visitObject->uploadStatus==Visit::NOT_DONE){
			return 2;
		}
	}else if($visitObject->qcStatus==Visit::QC_NOT_DONE){
		return 4;
	}else if($visitObject->qcStatus==Visit::QC_CORRECTIVE_ACTION_ASKED){
		return 5;
	}else if($visitObject->qcStatus==Visit::QC_REFUSED){
		return 6;
	}else if($visitObject->reviewStatus==Visit::NOT_DONE){
		return 7;
	}else if($visitObject->reviewStatus==Form_Processor::ONGOING){
		return 8;
	}else if($visitObject->reviewStatus==Form_Processor::WAIT_ADJUDICATION){
		return 9;
	}else if($visitObject->reviewStatus==Form_Processor::DONE){
		return 10;
	}
	
}