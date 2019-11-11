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
 * Collect study's data from Orthanc Server
 */

 class Orthanc_Study{
	public $studyOrthancID;
	public $studyIsStable;
	public $studyDate;
	public $studyTime;
	public $studyDescription;
	public $studyInstanceUID;
	public $studyLastUpdate;
	
	public $countInstances;
	public $diskSizeMb;
	public $uncompressedSizeMb;
	
	public $seriesInStudy;
	public $numberOfSeriesInStudy;
	
	public $parentPatientName;
	public $parentPatientID;
	public $parentPartientOrthancID;
	
	public $orthancSeries;
	
	
	private $url;
	private $context;
	
	public function __construct($studyOrthancId, $url, $context){
	    //Set http adress of orthanc
	    if($url==null && $context==null){
	        $linkpdo=Session::getLinkpdo();
	        
	        $this->url=GAELO_ORTHANC_PACS_ADDRESS.':'.GAELO_ORTHANC_PACS_PORT;
	    	$this->context = array(
	    			'http' => array(
	    			    'header'  => "Authorization: Basic " . base64_encode( GAELO_ORTHANC_PACS_LOGIN.':'.GAELO_ORTHANC_PACS_PASSWORD )
	    			)
	    	);
	    	
	    }else{
	    	$this->url=$url;
	    	$this->context = $context;	
	    }
	    
		//put current study orthanc ID in memory
		$this->studyOrthancID=$studyOrthancId;
		
	}
	
	/**
	 *Get study related tags and store them in this object
	 */
	public function retrieveStudyData(){
	    $context  = stream_context_create($this->context);
	    $json = file_get_contents($this->url.'/studies/'.$this->studyOrthancID, false, $context);
		//On le decode
		$studiesJson=json_decode($json, true);
		//On cree un object patient avec les information
		$this->studyDate=$studiesJson['MainDicomTags']['StudyDate'];
		$this->studyTime=$studiesJson['MainDicomTags']['StudyTime'];
		$this->studyDescription=$studiesJson['MainDicomTags']['StudyDescription'];
		$this->studyInstanceUID=$studiesJson['MainDicomTags']['StudyInstanceUID'];
		$this->studyLastUpdate=$studiesJson['LastUpdate'];
		$this->seriesInStudy= $studiesJson['Series'];
		$this->numberOfSeriesInStudy=sizeof($studiesJson['Series']);
		$this->studyIsStable= $studiesJson['IsStable'];
		$this->parentPatientName=$studiesJson['PatientMainDicomTags']['PatientName'];
		$this->parentPatientID=$studiesJson['PatientMainDicomTags']['PatientID'];
		$this->parentPartientOrthancID=$studiesJson['ParentPatient'];
		
		//add statistics info
		$this->retrieveStudyStatistics();
		//add series tag
		$this->getSeriesMainTag();
	}
	
	/**
	 *Get study statistics info (size in MB, number of instances) and store them in this object
	 */
	private function retrieveStudyStatistics(){
	    $context  = stream_context_create($this->context);
	    $json = file_get_contents($this->url.'/studies/'.$this->studyOrthancID.'/statistics/', false, $context);
	    //On le decode
	    $statisticsJson=json_decode($json, true);
	    $this->countInstances=$statisticsJson['CountInstances'];
	    $this->diskSizeMb=$statisticsJson['DiskSizeMB'];
	    $this->uncompressedSizeMb=$statisticsJson['UncompressedSizeMB'];
	    
	}
	
	/**
	 * Create a series object with series data for each series and store them in this object
	 */
	private function getSeriesMainTag(){
		foreach ($this->seriesInStudy as $seriesID){
			$series=new Orthanc_Serie($seriesID, $this->url, $this->context);
			@$series->retrieveSeriesData();
			$this->orthancSeries[]=$series;
		}
	}
}