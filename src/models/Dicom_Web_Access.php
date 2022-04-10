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
 * Check access for each call of the DICOMWeb Protocol (for OHIF / Viewer integration)
 * @author salim
 *
 */
class Dicom_Web_Access {

	private $parsedUrl;
	private $userObject;
	private $userRole;
	private $linkpdo;
	private $level;
    
	public function __construct(string $requestedURI, User $userObject, string $userRole, PDO $linkpdo) {
		$this->userObject=$userObject;
		$this->userRole=$userRole;
		$this->linkpdo=$linkpdo;
        
		//error_log(print_r)
		$url = parse_url($requestedURI);
		$this->level = $this->getLevel($url);
		$this->parsedUrl = $url;
        
	}
    
	/**
	 * Output the decision for access allowance
	 * @return boolean
	 */
	public function getDecision() {

		//Determine parent Visit ID depending of requested UID

		if($this->level === "patients"){

            $patientId = $this->getPatientID($this->parsedUrl);
			$patientEntity = new Patient($patientId, $this->linkpdo);
			return $this->userObject->isRoleAllowed($patientEntity->patientStudy, $this->userRole);

        }
        else if($this->level === "studies"){

            $requestedStudyInstanceUID = $this->getStudyInstanceUID($this->parsedUrl);
			$studyEntity = Study_Details::getStudyObjectByUID($requestedStudyInstanceUID, $this->linkpdo);
            $visitId = $studyEntity->idVisit;

        }else if ($this->level === "series"){

            $requestedSeriesInstanceUID = $this->getSeriesInstanceUID($this->parsedUrl);
			$seriesEntity = Series_Details::getSerieObjectByUID($requestedSeriesInstanceUID, $this->linkpdo);
            $visitId = $seriesEntity->studyDetailsObject->idVisit;

        }

		//Return test of acess allowance
		return $this->isAccessAllowedForUser($visitId);
	}
    
    
	/**
	 * Check that visit is granter for the calling user (still awaiting review or still awaiting QC)
	 * @param string $id_visit
	 * @return boolean
	 */
	private function isAccessAllowedForUser(string $id_visit) {
        
		$visitObject=new Visit($id_visit, $this->linkpdo);
        
		//Check Visit Availability of the calling user
		if ($this->userRole == User::REVIEWER || ($this->userRole == User::INVESTIGATOR && $visitObject->uploadStatus == Visit::DONE)) {
			//Check that visit is in patient that is still awaiting for some reviews
			$visitCheck=$this->userObject->isVisitAllowed($id_visit, $this->userRole);
		}else if ($this->userRole == User::CONTROLLER) {
			//Check that QC status still require an action from Controller
			if (in_array($visitObject->stateQualityControl, array(Visit::QC_WAIT_DEFINITVE_CONCLUSION, Visit::QC_NOT_DONE))) {
				$visitCheck=$this->userObject->isVisitAllowed($id_visit, $this->userRole);
			}
		}else if ($this->userRole == User::SUPERVISOR) {
			$visitCheck=$this->userObject->isVisitAllowed($id_visit, $this->userRole);
		}else {
			//Other roles can't have access to images
			$visitCheck=false;
		}
        
		return $visitCheck;
        
	}


	private function getLevel(array $url) : string {

		$level = null;

        if( key_exists('query',  $url) ){
            $params = [];
            parse_str($url['query'], $params);

			if(key_exists('00100020',  $params)) {
                $level = "patients";
                return $level;
            };

            if(key_exists('0020000D',  $params)) {
                $level = "studies";
                return $level;
            };
        }

		if ($this->endsWith($url['path'], "/studies"))  $level = "patients";
        else if ($this->endsWith($url['path'], "/series"))  $level = "studies";
        else $level = "series";

		return $level;

    }

	private function getPatientID(array $url) : string {

		if( key_exists('query',  $url) ){
            $params = [];
            parse_str($url['query'], $params);
            if(key_exists('00100020',  $params)) return $params['00100020'];
        }
        return $this->getUID($url['path'], "patients");

	}


	private function getStudyInstanceUID(array $url) : string {
        if( key_exists('query',  $url) ){
            $params = [];
            parse_str($url['query'], $params);
            if(key_exists('0020000D',  $params)) return $params['0020000D'];
        }
        return $this->getUID($url['path'], "studies");
    }

    private function getSeriesInstanceUID(array $url)  : string {
        return $this->getUID($url['path'], "series");
    }


	 /**
     * Isolate the called Study or Series Instance UID
     * @return string
     */
    private function getUID(string $requestedURI, string $level): string
    {
        $studySubString = strstr($requestedURI, "/" . $level . "/");
        $studySubString = str_replace("/" . $level . "/", "", $studySubString);

        $endStudyUIDPosition = strpos($studySubString, "/");

        if ($endStudyUIDPosition) {
            $studyUID = substr($studySubString, 0, $endStudyUIDPosition);
        } else {
            $studyUID = $studySubString;
        };

        return $studyUID;
    }

	private function endsWith($haystack, $needle) {
		return substr_compare($haystack, $needle, -strlen($needle)) === 0;
	}

}