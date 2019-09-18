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
 * Collect serie's data from Orthanc Server
 */

Class Orthanc_Serie{
	
	public $serieOrthancID;
	public $parentStudyOrthancID;
	public $seriesManufacturer;
	public $seriesModelName;
	public $modality;
	public $seriesDate;
	public $seriesTime;
	public $seriesDescription;
	public $seriesInstanceUID;
	public $seriesNumber;
	public $seriesIsStable;
	public $seriesInstances;
	public $numberOfInstanceInOrthanc;
	public $lastUpdate;
	public $sharedTags;
	public $diskSizeMb;
	public $uncompressedSizeMb;
	public $patientWeight;
	public $injectedDose;
	public $injectedTime;
	public $injectedDateTime;
	public $injectedActivity;
	public $radiopharmaceutical;
	public $halfLife;
	public $sopClassUid;
	
	private $url;
	private $context;

	
	public function __construct($seriesOrthancID, $url, $context) {
	    //Set Orthanc http address
	    $this->url=$url;
	    $this->context = $context;
	    
		//Set the current serie's Orthanc ID
		$this->serieOrthancID=$seriesOrthancID;
	}
	
	/**
	 *Get Series related data and store them in this object
	 */
	public function retrieveSeriesData(){
	    $context  = stream_context_create($this->context);
	    //Store all shared tags
	    $this->sharedTags = json_decode(file_get_contents($this->url.'/series/'.$this->serieOrthancID.'/shared-tags', false, $context));
		//parse main series informations
	    $json = file_get_contents($this->url.'/series/'.$this->serieOrthancID, false, $context);
		$seriesJson=json_decode($json, true);
		//add needed informations in the current object
		$this->seriesManufacturer=$seriesJson['MainDicomTags']['Manufacturer'];
		$this->modality=$seriesJson['MainDicomTags']['Modality'];
		$this->seriesDate=$seriesJson['MainDicomTags']['SeriesDate'];
		$this->seriesTime=$seriesJson['MainDicomTags']['SeriesTime'];
		$this->seriesDescription=$seriesJson['MainDicomTags']['SeriesDescription'];
		$this->seriesInstanceUID=$seriesJson['MainDicomTags']['SeriesInstanceUID'];
		$this->seriesNumber=$seriesJson['MainDicomTags']['SeriesNumber'];
		$this->seriesIsStable= $seriesJson['IsStable'];
		$this->parentStudyOrthancID=$seriesJson['ParentStudy'];
		$this->seriesInstances=$seriesJson['Instances'];
		$this->numberOfInstanceInOrthanc=sizeof($seriesJson['Instances']);
		$this->lastUpdate=$seriesJson['LastUpdate'];
		
		//add instance data using the first Instance Orthanc ID
		$this->retrieveInstancesData($seriesJson['Instances'][0]);
		
		//add statistics data
		$this->retrieveSeriesStatistics();
		
	}
	
	/**
	 * Get statistics of the series (size in MB)
	 */
	private function retrieveSeriesStatistics(){
	    $context  = stream_context_create($this->context);
	    $json = file_get_contents($this->url.'/series/'.$this->serieOrthancID.'/statistics/', false, $context);
	    $statisticsJson=json_decode($json, true);
	    $this->diskSizeMb=$statisticsJson['DiskSizeMB'];
	    $this->uncompressedSizeMb=$statisticsJson['UncompressedSizeMB'];
	    
	}
	
	/**
	 * Store some data only available in the Instance level 
	 * @param $instanceID
	 */
	private function retrieveInstancesData($instanceID){
	    $context  = stream_context_create($this->context);
	    $json = file_get_contents($this->url.'/instances/'.$instanceID.'/tags/', false, $context);
	    $instanceJson=json_decode($json, true);
	    $this->patientWeight=$instanceJson['0010,1030']['Value'];
	    $this->seriesModelName=$instanceJson['0008,1090']['Value'];
	    $this->injectedDose=$instanceJson['0054,0016']['Value'][0]['0018,1074']['Value'];
	    //SK InjectedTime est deprecie en faveur de DateTime, A surveiller pour la suite
	    $this->injectedTime=$instanceJson['0054,0016']['Value'][0]['0018,1072']['Value'] ;
	    $this->injectedDateTime=$instanceJson['0054,0016']['Value'][0]['0018,1078']['Value'] ;
	    $this->injectedActivity=$instanceJson['0054,0016']['Value'][0]['0018,1077']['Value'];
	    $this->radiopharmaceutical=$instanceJson['0054,0016']['Value'][0]['0018,0031']['Value'];
	    $this->halfLife=$instanceJson['0054,0016']['Value'][0]['0018,1075']['Value'];
	    $this->sopClassUid=$instanceJson['0008,0016']['Value'];
	}
	
	/**
	 * Return if this serie  in a secondary capture type
	 * @return boolean
	 */
	public function isSecondaryCapture(){
		$scUids[]="1.2.840.10008.5.1.4.1.1.7";
		$scUids[]="1.2.840.10008.5.1.4.1.1.7.1";
		$scUids[]="1.2.840.10008.5.1.4.1.1.7.2";
		$scUids[]="1.2.840.10008.5.1.4.1.1.7.3";
		$scUids[]="1.2.840.10008.5.1.4.1.1.7.4";
		$scUids[]="1.2.840.10008.5.1.4.1.1.88.11";
		$scUids[]="1.2.840.10008.5.1.4.1.1.88.22";
		$scUids[]="1.2.840.10008.5.1.4.1.1.88.33";
		$scUids[]="1.2.840.10008.5.1.4.1.1.88.40";
		$scUids[]="1.2.840.10008.5.1.4.1.1.88.50";
		$scUids[]="1.2.840.10008.5.1.4.1.1.88.59";
		$scUids[]="1.2.840.10008.5.1.4.1.1.88.65";
		$scUids[]="1.2.840.10008.5.1.4.1.1.88.67";
		
		if(in_array($this->sopClassUid, $scUids)){
			return true;
		}else {
			return false;
		}
		
	}
	
}