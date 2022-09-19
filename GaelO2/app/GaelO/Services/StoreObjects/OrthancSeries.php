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

Class OrthancSeries {

    private OrthancService $orthancService;

    public string $serieOrthancID;

	public string $parentStudyOrthancID;
	public ?string $manufacturer;
	public ?string $modelName;
	public ?string $modality;
	public ?string $seriesDate;
	public ?string $seriesTime;
	public ?string $seriesDescription;
	public string $seriesInstanceUID;
	public ?string $seriesNumber;
	public bool $seriesIsStable;
	public array $seriesInstances;
	public int $numberOfInstanceInOrthanc;
	public string $lastUpdate;
	public int $diskSizeMb;
	public int $uncompressedSizeMb;
	public $patientWeight;
    public $injectedDose;
    public $injectedTime;
	public $injectedDateTime;
	public $injectedActivity;
	public $radiopharmaceutical;
	public $halfLife;
	public string $sopClassUid;

	public function __construct(OrthancService $orthancService) {
		$this->orthancService=$orthancService;
    }

    public function setSeriesOrthancID(string $seriesOrthancID){
        $this->serieOrthancID=$seriesOrthancID;
    }

	/**
	 *Get Series related data and store them in this object
	 */
	public function retrieveSeriesData() {
		$seriesDetails=$this->orthancService->getOrthancRessourcesDetails(Constants::ORTHANC_SERIES_LEVEL, $this->serieOrthancID);

		//add needed informations in the current object
		$this->manufacturer=$seriesDetails['MainDicomTags']['Manufacturer'] ?? null;
		$this->modality=$seriesDetails['MainDicomTags']['Modality'] ?? null;
		$this->seriesDate=$seriesDetails['MainDicomTags']['SeriesDate'] ?? null;
		$this->seriesTime=$seriesDetails['MainDicomTags']['SeriesTime'] ?? null;
		$this->seriesDescription=$seriesDetails['MainDicomTags']['SeriesDescription'] ?? null;
		$this->seriesInstanceUID=$seriesDetails['MainDicomTags']['SeriesInstanceUID'];
		$this->seriesNumber=$seriesDetails['MainDicomTags']['SeriesNumber'] ?? null;
		$this->seriesIsStable=$seriesDetails['IsStable'];
		$this->parentStudyOrthancID=$seriesDetails['ParentStudy'];
		$this->seriesInstances=$seriesDetails['Instances'];
		$this->numberOfInstanceInOrthanc=sizeof($seriesDetails['Instances']);
		$this->lastUpdate=$seriesDetails['LastUpdate'];

		//add instance data using the first Instance Orthanc ID
		$this->retrieveInstancesData($seriesDetails['Instances'][0]);

		//add statistics data
		$this->retrieveSeriesStatistics();

	}

	/**
	 * Get statistics of the series (size in MB)
	 */
	private function retrieveSeriesStatistics() {
        $statistics=$this->orthancService->getOrthancRessourcesStatistics(Constants::ORTHANC_SERIES_LEVEL, $this->serieOrthancID);
		$this->diskSizeMb=$statistics['DiskSizeMB'];
		$this->uncompressedSizeMb=$statistics['UncompressedSizeMB'];
	}

	/**
	 * Store some data only available in the Instance level
	 * @param $instanceID
	 */
	private function retrieveInstancesData($instanceOrthancID) {
		$instanceTags = $this->orthancService->getInstanceTags($instanceOrthancID);
		$this->patientWeight=is_numeric($instanceTags->getPatientWeight()) ? $instanceTags->getPatientWeight() : null;
		$this->modelName = $instanceTags->getModelName();
		$this->injectedDose = is_numeric($instanceTags->getInjectedDose()) ? $instanceTags->getInjectedDose() : null;
        $this->injectedTime = $instanceTags->getInjectedTime();
        $this->injectedDateTime = $instanceTags->getInjectedDateTime();
		$this->injectedActivity = is_numeric($instanceTags->getInjectedActivity())? $instanceTags->getInjectedActivity() : null;
		$this->radiopharmaceutical = $instanceTags->getRadiopharmaceutical();
		$this->halfLife=is_numeric($instanceTags->getHalfLife())? $instanceTags->getHalfLife() : null;
		$this->sopClassUid= $instanceTags->getSOPClassUID();
	}

	/**
	 * Return if this serie  in a secondary capture type
	 * @return boolean
	 */
	public function isSecondaryCapture() {
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

		if (in_array($this->sopClassUid, $scUids)) {
			return true;
		} else {
			return false;
		}

	}

}
