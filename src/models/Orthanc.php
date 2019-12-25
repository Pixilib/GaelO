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
 * Main class for Orthanc communication, serve usefull APIs (get dicom tags, send to peer, download zip...)
 * 
 * If Orthanc is using HTTPS :
 * the php_openssl extension must exist and be enabled
 * In the php.ini file you should add this lines if not exists:
 * extension=php_openssl.dll
 * allow_url_fopen = On
*/

Class Orthanc {
	
	private $url;
	private $context;
	
	/**
	 * Build connexion string in Orthanc, boolean to connect with Orthanc Exposed or Orthanc PACS
	 * @param boolean $exposed
	 */
	public function __construct(bool $exposed=false){
	    //Set Time Limit at 3H as operation could be really long
	    set_time_limit(10800);
        //Set address of Orthanc server
        if($exposed){
            $this->url=GAELO_ORTHANC_EXPOSED_INTERNAL_ADDRESS.':'.GAELO_ORTHANC_EXPOSED_INTERNAL_PORT;
            $this->context = array(
                'http' => array(
                    'header'  => "Authorization: Basic " . base64_encode(GAELO_ORTHANC_EXPOSED_INTERNAL_LOGIN.':'.GAELO_ORTHANC_EXPOSED_INTERNAL_PASSWORD)
	   			));
        }else{
            $this->url=GAELO_ORTHANC_PACS_ADDRESS.':'.GAELO_ORTHANC_PACS_PORT;
            $this->context = array(
	   			'http' => array(
	   			    'header'  => "Authorization: Basic " . base64_encode(GAELO_ORTHANC_PACS_LOGIN.':'.GAELO_ORTHANC_PACS_PASSWORD)
	   			) );
	   }

	}
	
	/**
	 * Return list of Peers declared in Orthanc
	 * @return mixed
	 */
	public function getPeers(){
	    $context =stream_context_create($this->context);
	    $json = file_get_contents($this->url.'/peers/',false, $context);
	    $peers=json_decode($json, true);
	    return $peers;
	}
	
	/**
	 * Search function in Orthanc, search the Patients/Studies/Series level in Orthanc and return the raw response in JSON
	 * @param string $level
	 * @param string $patientID
	 * @param string $patientName
	 * @param string $studyDate
	 * @param string $studyUID
	 * @param string $accessionNumber
	 * @param string $studyDescription
	 * @return string json
	 */
	public function searchInOrthanc(string $level, string $patientID, string $patientName, string $studyDate, string $studyUID, string $accessionNumber, string $studyDescription){
		
		$queryDetails=array(
				'PatientID' => $patientID,
				'PatientName' => $patientName,
				'StudyDate' => $studyDate,
		        'StudyInstanceUID' => $studyUID,
				'AccessionNumber'=> $accessionNumber,
				'StudyDescription'=> $studyDescription,
		);
		
		$query=array(
				'Level' => $level,
				'CaseSensitive' => false,
				'Expand' => false,
				'Query' => $queryDetails,
				
		);
		
		$opts = array('http' =>
				array(
						'method'  => 'POST',
						'content' => json_encode($query),
				        'header'=>  ['Content-Type: application/json Accept: application/json ',$this->context['http']['header']]
				)
		);

		$context  = stream_context_create($opts);
		$resultJson = file_get_contents($this->url.'/tools/find', false, $context);
		$result=json_decode($resultJson);
		
		return $result;
	}
	
	/**
	 * return the ZIP as temp file containing the Orthanc ID ressources dicoms
	 * @param array $uidList
	 * @return string temporary file path
	 */
	public function getZipTempFile(array $uidList){
	   
	    if( !is_array($uidList)){
	        $uidList=array($uidList);
	    }
	    
	    $opts = array('http' =>
	        array(
	            'method'  => 'POST',
	            'content' => json_encode($uidList),
	            'timeout' => 3600,
	            'header'=>  ['Content-Type: application/json', 'Accept: application/zip',$this->context['http']['header'] ]
	        )
	    );
	    
	    $context = stream_context_create($opts);
	    
 	    $temp = tempnam(ini_get('upload_tmp_dir'), 'TMP_');
	    
	    $nbbytes=file_put_contents($temp , fopen($this->url.'/tools/create-archive','rb', false, $context));
	    
	    return $temp;
	}
	
	
	/**
	 * Delete in Orthanc patients / study / series depending on Level and Orthanc ID
	 * @param  $level
	 * @param  $uidList
	 */
	public function deleteFromOrthanc(string $level, string $uid){
	    
	    $opts = array('http' =>
	        array(
	            'method'  => 'DELETE',
	            'header'=>  ['Content-Type: application/json Accept: application/json',$this->context['http']['header']]
	        )
	    );
	    
	    $context  = stream_context_create($opts);
	    $result = file_get_contents($this->url.'/'.$level.'/'.$uid, false, $context);
	    
	}
	
	/**
	 * Add Peer declaration to Orthanc
	 * @param string $name name of the peer
	 * @param string $url URL with http / https and port
	 * @param string $username
	 * @param string $password
	 * @return mixed
	 */
	public function addPeer(string $name , string $url, string $username, string $password){
	    
	    $peerValues['Username']=$username;
	    $peerValues['Password']=$password;
	    $peerValues['Url']=$url;
	    $opts = array('http' =>
	        array(
	            'method'  => 'PUT',
	            'content' => json_encode($peerValues),
	            'header'=>  ['Content-Type: application/json Accept: application/json ',$this->context['http']['header']]
	        )
	    );
	    
	    $context  = stream_context_create($opts);
	    $resultJson = file_get_contents($this->url.'/peers/'.$name, false, $context);
	    
	    $result=json_decode($resultJson);
	    return $result;
	    
	}
	
	/**
	 * Remove Peer declaration from Orthanc
	 * @param string $name
	 */
	public function removePeer(string $name){
	    
	    $opts = array('http' =>
	        array(
	            'method'  => 'DELETE',
	            'header'=>  ['Content-Type: application/json Accept: application/json ',$this->context['http']['header']]
	        )
	    );
	    
	    $context  = stream_context_create($opts);
	    $resultJson = file_get_contents($this->url.'/peers/'.$name, false, $context);
	    
	    $result=json_decode($resultJson);
	    
	    return $result;
	}
	
	/**
	 * Remove all peers from orthanc
	 */
	public function removeAllPeers(){
	    
	    $peers=$this->getPeers();
	    
	    foreach ($peers as $peer){
	        $this->removePeer($peer);
	    }
	    
	}
	
	/**
	 * Test if a peer has Orthanc Peer Accelerator
	 * @param string $peer
	 * @return boolean
	 */
	public function isPeerAccelerated(string $peer){
	    $context =stream_context_create($this->context);
	    $json = file_get_contents($this->url.'/transfers/peers/',false, $context);
	    $peersTest=json_decode($json, true);
	    
	    if($peersTest[$peer]=="installed"){
	        return true;
	    }
	    
	    return false;
	}
	
	/**
	 * Send id list of DICOM to a peer
	 * @param string $peer
	 * @param array $ids
	 */
	public function sendToPeer(string $peer, array $ids){
		
		$opts = array('http' =>
				array(
						'method'  => 'POST',
                        "timeout" => 300, 
						'content' => json_encode($ids),
						'header'=>  ['Content-Type: application/json Accept: application/json',$this->context['http']['header']]
				)
		);
		
		$context = stream_context_create($opts);
		$result = file_get_contents($this->url.'/peers/'.$peer.'/store', false, $context);
		return $result;
	    
	}
	
	/**
	 * Send to Orthanc peer but asynchroneously
	 * @param string $peer
	 * @param array $ids
	 * @return string
	 */
	public function sendToPeerAsync(string $peer, array $ids){
	    $data['Synchronous']=false;
	    $data['Resources']=$ids;
	    
	    $opts = array('http' =>
	        array(
	            'method'  => 'POST',
	            'content' => json_encode($data),
	            'header'=>  ['Content-Type: application/json Accept: application/json',$this->context['http']['header']]
	        )
	    );
	    
	    $context = stream_context_create($opts);
	    $result = file_get_contents($this->url.'/peers/'.$peer.'/store', false, $context);
	    return $result;
	}
	
	/**
	 * Send to peer with transfers accelerator plugin
	 * @param string $peer
	 * @param array $ids
	 * @param bool $gzip
	 * @return string
	 */
	public function sendToPeerAsyncWithAccelerator(string $peer, array $ids, bool $gzip){
	    
	    //If Peer dosen't have accelerated transfers fall back to regular orthanc peer transfers
	    if( ! $this->isPeerAccelerated($peer)){
	        $answer=$this->sendToPeerAsync($peer, $ids);
	        return $answer;
	    }
	    
	    if(!$gzip) $data['Compression']="none" ; else $data['Compression']="gzip";
	    
	    $data['Peer']=$peer;
	    
	    
	    foreach ($ids as $serieID){
	        $resourceSeries['Level']="Series";
	        $resourceSeries['ID']=$serieID;
	        $data['Resources'][]=$resourceSeries;
	    }
	    
	    $opts = array('http' =>
	        array(
	            'method'  => 'POST',
	            'content' => json_encode($data),
	            'header'=>  ['Content-Type: application/json Accept: application/json',$this->context['http']['header']]
	        )
	    );
	    
	    $context = stream_context_create($opts);
	    $result = file_get_contents($this->url.'/transfers/send', false, $context);
	    
	    return $result;
	    
	}
	
	/**
	 * Import a file in Orthanc using the POST API
	 * @param string $file (path)
	 * @return string
	 */
	public function importFile(string $file){
	    
	    try{
    	    $opts = array('http' =>
    	        array(
    	            'method'  => 'POST',
    	            'content' => file_get_contents($file),
    	            'header'=>  ['Content-Type: application/dicom Accept: application/json',"content-length: ".filesize($file),$this->context['http']['header']]
    	        )
    	    );
    	    
    	    $context = stream_context_create($opts);
    	    $result = file_get_contents($this->url.'/instances', false, $context);
    	    
	    }catch(Exception $e1){
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
	public function Anonymize(string $studyID, string $profile, string $patientCode, string $visitType, string $studyName){
	    
	    $jsonAnonQuery=$this->buildAnonQuery($profile, $patientCode, $patientCode, $visitType, $studyName);
	    
	    $opts = array('http' =>
	        array(
	            'method'  => 'POST',
	            "timeout" => 300,
	            'content' => json_encode($jsonAnonQuery),
	            'header'=>  ['Content-Type: application/json Accept: application/json',$this->context['http']['header']]
	        )
	    );
	    
	    $context = stream_context_create($opts);

        $result = file_get_contents($this->url."/studies/".$studyID."/anonymize", false, $context);
        
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
	private function buildAnonQuery(string $profile, string $newPatientName, string $newPatientID, string $newStudyDescription, string $clinicalStudy){
    	    
        $tagsObjects=[];
        if($profile=="Default"){
            $date=TagAnon::KEEP;
            $body=TagAnon::KEEP;
            
            $tagsObjects[]=new TagAnon("0010,0030", TagAnon::REPLACE, "19000101"); // BirthDay
            $tagsObjects[]=new TagAnon("0008,1030", TagAnon::REPLACE, $newStudyDescription); //studyDescription
            $tagsObjects[]=new TagAnon("0008,103E", TagAnon::KEEP); //series Description
           
    
        }else if($profile=="Full"){
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
    	$tagsObjects[]=new TagAnon("0008,0050", TagAnon::REPLACE, $clinicalStudy);  // Accession Number contains study name
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
    	
    	foreach($tagsObjects as $tag) {
    	    
    	    if($tag->choice==TagAnon::REPLACE){
    	        $jsonArrayAnon['Replace'][$tag->tag]=$tag->newValue;
    	    }else if($tag->choice==TagAnon::KEEP){
    	        $jsonArrayAnon['Keep'][]=$tag->tag;
            }
            
    	}
    	
    	return $jsonArrayAnon;
    	
    }
    
    /**
     * Remove secondary captures in the study
     * @param string $orthancStudyID
     */
    private function removeSC(string $orthancStudyID){
    	
    	$studyOrthanc=new Orthanc_Study($orthancStudyID, $this->url, $this->context);
    	$studyOrthanc->retrieveStudyData();
    	$seriesObjects=$studyOrthanc->orthancSeries;
    	foreach ($studyOrthanc->orthancSeries as $serie){
    		if($serie->isSecondaryCapture() ){
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
    public function getJobDetails(String $jobId){
        
        $context =stream_context_create($this->context);
        $json = file_get_contents($this->url.'/jobs/'.$jobId, false, $context);
        
        return json_decode($json, true);
        
    }

}