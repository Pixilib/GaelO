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

namespace App\GaelO\Services\StoreObjects;

use App\GaelO\Constants\Constants;
use App\GaelO\Services\OrthancService;


/**
 * Collect study's data from Orthanc Server
 */

 class OrthancStudy {
    private OrthancService $orthancService;

    public string $studyOrthancID;

	public bool $studyIsStable;
	public ?string $studyDate;
	public ?string $studyTime;
	public ?string $studyDescription;
	public string $studyInstanceUID;
	public string $studyLastUpdate;

	public int $countInstances;
	public int $diskSizeMb;
	public int $uncompressedSizeMb;

	public array $seriesInStudy = [];
	public int $numberOfSeriesInStudy;

	public ?string $parentPatientName;
	public ?string $parentPatientID;
	public string $parentPartientOrthancID;

	public array $orthancSeries = [];

	public function __construct(OrthancService $orthancService) {
        $this->orthancService = $orthancService;
    }

    public function setStudyOrthancID($studyOrthancID){
        $this->studyOrthancID = $studyOrthancID;
    }

	/**
	 *Get study related tags and store them in this object
	 */
	public function retrieveStudyData() {
		$studyDetails=$this->orthancService->getOrthancRessourcesDetails(Constants::ORTHANC_STUDIES_LEVEL, $this->studyOrthancID);

		//On cree un object patient avec les information
		$this->studyDate=$studyDetails['MainDicomTags']['StudyDate'] ?? null;
		$this->studyTime=$studyDetails['MainDicomTags']['StudyTime'] ?? null;
		$this->studyDescription=$studyDetails['MainDicomTags']['StudyDescription'] ?? null;
		$this->studyInstanceUID=$studyDetails['MainDicomTags']['StudyInstanceUID'];
		$this->studyLastUpdate=$studyDetails['LastUpdate'];
		$this->seriesInStudy=$studyDetails['Series'];
		$this->numberOfSeriesInStudy=sizeof($studyDetails['Series']);
		$this->studyIsStable=$studyDetails['IsStable'];
		$this->parentPatientName=$studyDetails['PatientMainDicomTags']['PatientName'] ?? null;
		$this->parentPatientID=$studyDetails['PatientMainDicomTags']['PatientID'] ?? null;
		$this->parentPartientOrthancID=$studyDetails['ParentPatient'];

		//add statistics info
		$this->retrieveStudyStatistics();
		//add series tag
		$this->getSeriesMainTags();
	}

	/**
	 *Get study statistics info (size in MB, number of instances) and store them in this object
	 */
	private function retrieveStudyStatistics() {
        $statistics=$this->orthancService->getOrthancRessourcesStatistics(Constants::ORTHANC_STUDIES_LEVEL, $this->studyOrthancID);
		$this->countInstances=$statistics['CountInstances'];
		$this->diskSizeMb=$statistics['DiskSizeMB'];
		$this->uncompressedSizeMb=$statistics['UncompressedSizeMB'];

	}

	/**
	 * Create a series object with series data for each series and store them in this object
	 */
	private function getSeriesMainTags() {
		foreach ($this->seriesInStudy as $seriesOrthancID) {
            $series=new OrthancSeries($this->orthancService);
            $series->setSeriesOrthancID($seriesOrthancID);
			@$series->retrieveSeriesData();
			$this->orthancSeries[]=$series;
		}
	}
}
