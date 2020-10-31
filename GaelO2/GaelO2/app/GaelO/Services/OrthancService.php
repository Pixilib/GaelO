<?php

namespace App\GaelO\Services;

use App\GaelO\Adapters\HttpClientAdapter;
use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Constants\SettingsConstants;

class OrthancService {

    public function __construct(HttpClientAdapter $httpClientAdapter, LaravelFunctionAdapter $laravelFunctionAdapter) {
        $this->httpClientAdapter = $httpClientAdapter;
        $this->laravelFunctionAdapter = $laravelFunctionAdapter;
    }

    public function setOrthancServer(bool $storage){
        //Set Time Limit at 3H as operation could be really long
		set_time_limit(10800);
		//Set address of Orthanc server
		if ($storage) {
			$address = $this->laravelFunctionAdapter->getConfig(SettingsConstants::ORTHANC_STORAGE_ADDRESS);
            $port = $this->laravelFunctionAdapter->getConfig(SettingsConstants::ORTHANC_STORAGE_PORT);
            $login = $this->laravelFunctionAdapter->getConfig(SettingsConstants::ORTHANC_STORAGE_LOGIN);
            $password = $this->laravelFunctionAdapter->getConfig(SettingsConstants::ORTHANC_STORAGE_PASSWORD);
		}else {
            $address = $this->laravelFunctionAdapter->getConfig(SettingsConstants::ORTHANC_TEMPORARY_ADDRESS);
            $port = $this->laravelFunctionAdapter->getConfig(SettingsConstants::ORTHANC_TEMPORARY_PORT);
            $login = $this->laravelFunctionAdapter->getConfig(SettingsConstants::ORTHANC_TEMPORARY_LOGIN);
            $password = $this->laravelFunctionAdapter->getConfig(SettingsConstants::ORTHANC_TEMPORARY_PASSWORD);

       }

       $this->httpClientAdapter->setAddress($address, $port);
       $this->httpClientAdapter->setBasicAuthentication($login, $password);

    }

    public function getOrthancPeers(){
        return $this->httpClientAdapter->requestJson('GET', '/peers')->getJsonBody();
    }

    public function addPeer(string $name, string $url, string $username, string $password) {

        $data=array(
            'Username'=> $username,
            'Password'=> $password,
            'Url'=> $url
        );

        return $this->httpClientAdapter->requestJson('PUT', '/peers/'.$name, $data);

	}

	/**
	 * Remove Peer declaration from Orthanc
	 * @param string $name
	 */
	public function deletePeer(string $name) {
        return $this->httpClientAdapter->request('DELETE', '/peers/'.$name);
    }

    /**
	 * Remove all peers from orthanc
	 */
	public function removeAllPeers() {
        $peers=$this->getOrthancPeers();

		foreach ($peers as $peer) {
			$this->deletePeer($peer);
		}

	}


    public function searchInOrthanc(string $level, string $patientID,
                string $patientName, string $studyDate,
                string $studyUID, string $accessionNumber,
                string $studyDescription) {

		$query=array(
				'Level' => $level,
				'CaseSensitive' => false,
				'Expand' => false,
				'Query' => array(
                    'PatientID' => $patientID,
                    'PatientName' => $patientName,
                    'StudyDate' => $studyDate,
                    'StudyInstanceUID' => $studyUID,
                    'AccessionNumber'=> $accessionNumber,
                    'StudyDescription'=> $studyDescription,
                )

		);

        return $this->httpClientAdapter->requestJson('POST', '/tools/find', $query);

    }

    public function deleteFromOrthanc(string $level, string $uid) {
        return $this->httpClientAdapter->request('DELETE', '/'.$level.'/'.$uid);
	}

    public function getZipStream(){
        //TO DO
    }


    //SK A CONTINUER ICI

    /**
	 * Test if a peer has Orthanc Peer Accelerator
	 * @param string $peer
	 * @return boolean
	 */
	public function isPeerAccelerated(string $peer) {

        $peers = $this->httpClientAdapter->request('GET', '/transfers/peers/')->getJsonBody();

		if ($peers[$peer] == "installed") {
			return true;
		}

		return false;
	}

	/**
	 * Send to Orthanc ressources IDs to Orthanc peer
	 * @param string $peer
	 * @param array $ids
	 * @return string
	 */
	public function sendToPeer(string $peer, array $ids, bool $synchronous) {
        $data = [
            'Synchronous'=> $synchronous,
            'Resources'=> $ids
        ];

        return $this->httpClientAdapter->requestJson('POST', '/peers/'.$peer.'/store', $data);
	}

	/**
	 * Send to peer with transfers accelerator plugin
	 * @param string $peer
	 * @param array $ids
	 * @param bool $gzip
	 * @return string
	 */
	public function sendToPeerAsyncWithAccelerator(string $peer, array $ids, bool $gzip) {

		//If Peer dosen't have accelerated transfers fall back to regular orthanc peer transfers
		if (!$this->isPeerAccelerated($peer)) {
			$answer=$this->sendToPeerAsync($peer, $ids);
			return $answer;
		}

		if (!$gzip) $data['Compression']="none"; else $data['Compression']="gzip";

		$data['Peer']=$peer;


		foreach ($ids as $serieID) {
			$resourceSeries['Level']="Series";
			$resourceSeries['ID']=$serieID;
			$data['Resources'][]=$resourceSeries;
		}

		$opts=array('http' =>
			array(
				'method'  => 'POST',
				'content' => json_encode($data),
				'header'=>  ['Content-Type: application/json Accept: application/json', $this->context['http']['header']]
			)
		);

		$context=stream_context_create($opts);
		$result=file_get_contents($this->url.'/transfers/send', false, $context);

		return $result;

	}

	/**
	 * Import a file in Orthanc using the POST API
	 * @param string $file (path)
	 * @return string
	 */
	public function importFile(string $file) {

		try {
			$opts=array('http' =>
				array(
					'method'  => 'POST',
					'content' => file_get_contents($file),
					'header'=>  ['Content-Type: application/dicom Accept: application/json', "content-length: ".filesize($file), $this->context['http']['header']]
				)
			);

			$context=stream_context_create($opts);
			$result=file_get_contents($this->url.'/instances', false, $context);

		}catch (Exception $e1) {
			error_log("Error during import Dcm ".$e1->getMessage());
		}
		return $result;

	}

	/**
	 * Anonymize a study ressources according to Anon Profile
	 * Return the Anonymized Orthanc ID
	 * @param string $studyID
	 * @param string $profile
	 * @param string $patientCode
	 * @param string $visitType
	 * @param string $studyName
	 * @return string
	 */
	public function Anonymize(string $studyID, string $profile, string $patientCode, string $visitType, string $studyName) {

		$jsonAnonQuery=$this->buildAnonQuery($profile, $patientCode, $patientCode, $visitType, $studyName);

		$opts=array('http' =>
			array(
				'method'  => 'POST',
				"timeout" => 300,
				'content' => json_encode($jsonAnonQuery),
				'header'=>  ['Content-Type: application/json Accept: application/json', $this->context['http']['header']]
			)
		);

		$context=stream_context_create($opts);

		$result=file_get_contents($this->url."/studies/".$studyID."/anonymize", false, $context);

		//get the resulting Anonymized study Orthanc ID
		$anonAnswer=json_decode($result, true);
		$anonymizedID=$anonAnswer['ID'];

		//Remove SC if any in the anonymized study
		$this->removeSC($anonymizedID);

		return $anonymizedID;

	}

	/**
	 * Build Anon Json post for Anon settings
	 * @param string $profile
	 * @param string $newPatientName
	 * @param string $newPatientID
	 * @param string $newStudyDescription
	 * @return string
	 */
	private function buildAnonQuery(string $profile, string $newPatientName, string $newPatientID, string $newStudyDescription, string $clinicalStudy) {

		$tagsObjects=[];
		if ($profile == "Default") {
			$date=TagAnon::KEEP;
			$body=TagAnon::KEEP;

			$tagsObjects[]=new TagAnon("0010,0030", TagAnon::REPLACE, "19000101"); // BirthDay
			$tagsObjects[]=new TagAnon("0008,1030", TagAnon::REPLACE, $newStudyDescription); //studyDescription
			$tagsObjects[]=new TagAnon("0008,103E", TagAnon::KEEP); //series Description


		}else if ($profile == "Full") {
			$date=TagAnon::CLEAR;
			$body=TagAnon::CLEAR;

			$tagsObjects[]=new TagAnon("0010,0030", TagAnon::REPLACE, "19000101"); // BirthDay
			$tagsObjects[]=new TagAnon("0008,1030", TagAnon::CLEAR); // studyDescription
			$tagsObjects[]=new TagAnon("0008,103E", TagAnon::CLEAR); //series Description
		}

		//List tags releted to Date
		$tagsObjects[]=new TagAnon("0008,0022", $date); // Acquisition Date
		$tagsObjects[]=new TagAnon("0008,002A", $date); // Acquisition DateTime
		$tagsObjects[]=new TagAnon("0008,0032", $date); // Acquisition Time
		$tagsObjects[]=new TagAnon("0038,0020", $date); // Admitting Date
		$tagsObjects[]=new TagAnon("0038,0021", $date); // Admitting Time
		$tagsObjects[]=new TagAnon("0008,0035", $date); // Curve Time
		$tagsObjects[]=new TagAnon("0008,0025", $date); // Curve Date
		$tagsObjects[]=new TagAnon("0008,0023", $date); // Content Date
		$tagsObjects[]=new TagAnon("0008,0033", $date); // Content Time
		$tagsObjects[]=new TagAnon("0008,0024", $date); // Overlay Date
		$tagsObjects[]=new TagAnon("0008,0034", $date); // Overlay Time
		$tagsObjects[]=new TagAnon("0040,0244", $date); // ...Start Date
		$tagsObjects[]=new TagAnon("0040,0245", $date); // ...Start Time
		$tagsObjects[]=new TagAnon("0008,0021", $date); // Series Date
		$tagsObjects[]=new TagAnon("0008,0031", $date); // Series Time
		$tagsObjects[]=new TagAnon("0008,0020", $date); // Study Date
		$tagsObjects[]=new TagAnon("0008,0030", $date); // Study Time
		$tagsObjects[]=new TagAnon("0010,21D0", $date); // Last menstrual date
		$tagsObjects[]=new TagAnon("0008,0201", $date); // Timezone offset from UTC
		$tagsObjects[]=new TagAnon("0040,0002", $date); // Scheduled procedure step start date
		$tagsObjects[]=new TagAnon("0040,0003", $date); // Scheduled procedure step start time
		$tagsObjects[]=new TagAnon("0040,0004", $date); // Scheduled procedure step end date
		$tagsObjects[]=new TagAnon("0040,0005", $date); // Scheduled procedure step end time

		// same for Body characteristics
		$tagsObjects[]=new TagAnon("0010,2160", $body); // Patient's ethnic group
		$tagsObjects[]=new TagAnon("0010,21A0", $body); // Patient's smoking status
		$tagsObjects[]=new TagAnon("0010,0040", $body); // Patient's sex
		$tagsObjects[]=new TagAnon("0010,2203", $body); // Patient's sex neutered
		$tagsObjects[]=new TagAnon("0010,1010", $body); // Patient's age
		$tagsObjects[]=new TagAnon("0010,21C0", $body); // Patient's pregnancy status
		$tagsObjects[]=new TagAnon("0010,1020", $body); // Patient's size
		$tagsObjects[]=new TagAnon("0010,1030", $body); // Patient's weight

		//Others
		$tagsObjects[]=new TagAnon("0008,0050", TagAnon::REPLACE, $clinicalStudy); // Accession Number contains study name
		$tagsObjects[]=new TagAnon("0010,0020", TagAnon::REPLACE, $newPatientID); //new Patient Name
		$tagsObjects[]=new TagAnon("0010,0010", TagAnon::REPLACE, $newPatientName); //new Patient Name

		// Keep some Private tags usefull for PET/CT or Scintigraphy
		$tagsObjects[]=new TagAnon("7053,1000", TagAnon::KEEP); //Phillips
		$tagsObjects[]=new TagAnon("7053,1009", TagAnon::KEEP); //Phillips
		$tagsObjects[]=new TagAnon("0009,103B", TagAnon::KEEP); //GE
		$tagsObjects[]=new TagAnon("0009,100D", TagAnon::KEEP); //GE
		$tagsObjects[]=new TagAnon("0011,1012", TagAnon::KEEP); //Other

		$jsonArrayAnon=[];
		$jsonArrayAnon['KeepPrivateTags']=false;
		$jsonArrayAnon['Force']=true;

		foreach ($tagsObjects as $tag) {

			if ($tag->choice == TagAnon::REPLACE) {
				$jsonArrayAnon['Replace'][$tag->tag]=$tag->newValue;
			}else if ($tag->choice == TagAnon::KEEP) {
				$jsonArrayAnon['Keep'][]=$tag->tag;
			}

		}

		return $jsonArrayAnon;

	}

	/**
	 * Remove secondary captures in the study
	 * @param string $orthancStudyID
	 */
	private function removeSC(string $orthancStudyID) {

		$studyOrthanc=new Orthanc_Study($orthancStudyID, $this->url, $this->context);
		$studyOrthanc->retrieveStudyData();
		$seriesObjects=$studyOrthanc->orthancSeries;
		foreach ($seriesObjects as $serie) {
			if ($serie->isSecondaryCapture()) {
				$this->deleteFromOrthanc("series", $serie->serieOrthancID);
				error_log("Deleted SC");
			}
		}

	}


	/**
	 * Return JobId details (get request in Orthanc)
	 * @param String $jobId
	 * @return mixed
	 */
	public function getJobDetails(String $jobId) {

		$context=stream_context_create($this->context);
		$json=file_get_contents($this->url.'/jobs/'.$jobId, false, $context);

		return json_decode($json, true);

	}

}
