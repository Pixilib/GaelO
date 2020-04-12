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
 * Fill Orthanc_Studies and Orthanc_Series table to link DICOM Orthanc storage with the web plateform
 * Used in the validation process of the upload
 */

class Fill_Orthanc_Table {
    
	private $linkpdo;
	private $username;
	private $studyOrthancObject;
	private $visitObject;

    
	public function __construct($id_visit, String $username, PDO $linkpdo){
		$this->username= $username;
		$this->linkpdo=$linkpdo;
		$this->visitObject=new Visit($id_visit, $linkpdo);
	}
    
	/**
	 * return study details of a study stored in Orthanc
	 * @param String $studyID
	 * @return array
	 */
	public function parseData(String $studyID){
		$studyData=new Orthanc_Study($studyID,null,null);
		$studyData->retrieveStudyData();
		$this->studyOrthancObject=$studyData;
		$studyDetails=get_object_vars($studyData);
		return $studyDetails;
	}
    
	/**
	 * Fill data in the database, write data for study and for each series
	 * Once done trigger the change upload status of visit to update upload status and eventually skip 
	 * local form and/or QC
	 */
	public function fillDB($anonFromOrthancStudyId){
        
		//Check that original OrthancID is unknown for this study
		if($this->visitObject->getParentStudyObject()->isOriginalOrthancNeverKnown($anonFromOrthancStudyId) ){
			try{
				//Fill la database
				$this->addToDbStudy($anonFromOrthancStudyId, $this->username);
                
				foreach ($this->studyOrthancObject->orthancSeries as $serie){
					//Fill series database
					$this->addtoDbSerie($serie);
				}
				$this->visitObject->changeUploadStatus(Visit::DONE, $this->username);
                
			}catch(Exception $e1){
				throw new Exception("Error during import ".$e1->getMessage());
			}
		}else{
			throw new Exception("Error during import Study Already Known");
		}
	}
    
	/**
	 * Private function to write into Orthanc_Studies DB
	 * @param string $anonFromOrthancStudyId
	 */
	private function addToDbStudy(string $anonFromOrthancStudyId){
			$studyAcquisitionDate2=$this->parseDateTime($this->studyOrthancObject->studyDate, 1);
			$studyAcquisitionTime2=$this->parseDateTime($this->studyOrthancObject->studyTime, 2);
            
			if($studyAcquisitionDate2!=null && $studyAcquisitionTime2!=null){
				$acquisitionDateTime=$studyAcquisitionDate2." ".$studyAcquisitionTime2;
			}
                        
			$addBdd=$this->linkpdo->prepare('INSERT INTO orthanc_studies (id_visit, 
                                            uploader, 
                                            upload_date, 
                                            acquisition_date, 
                                            acquisition_time, 
                                            acquisition_datetime, 
                                            study_orthanc_id, 
                                            anon_from_orthanc_id, 
                                            study_uid, 
                                            study_description, 
                                            patient_orthanc_id, 
                                            patient_name, 
                                            patient_id, 
                                            number_of_series, 
                                            number_of_instances, 
                                            disk_size, 
                                            uncompressed_disk_size)
            VALUES(:id_visit, 
                    :uploader, 
                    :upload_date, 
                    :acquisition_date, 
                    :acquisition_time, 
                    :acquisition_datetime, 
                    :study_orthanc_id, 
                    :anon_from_orthanc_id, 
                    :study_uid, 
                    :study_description, 
                    :patient_orthanc_id, 
                    :patient_name, 
                    :patient_id, 
                    :number_of_series, 
                    :number_of_instances, 
                    :disk_size, 
                    :uncompressed_disk_size)
            
            ON DUPLICATE KEY UPDATE uploader=:uploader, 
                                    upload_date=:upload_date, 
                                    acquisition_date=:acquisition_date, 
                                    acquisition_time=:acquisition_time, 
                                    acquisition_datetime=:acquisition_datetime, 
                                    anon_from_orthanc_id=:anon_from_orthanc_id, 
                                    study_uid=:study_uid, 
                                    study_description=:study_description, 
                                    patient_orthanc_id=:patient_orthanc_id, 
                                    patient_name=:patient_name, 
                                    patient_id=:patient_id, 
                                    number_of_series=:number_of_series, 
                                    number_of_instances=:number_of_instances, 
                                    disk_size=:disk_size, 
                                    uncompressed_disk_size=:uncompressed_disk_size, 
                                    deleted=0 ');
           
			$addBdd->execute(array(
				'id_visit'=>$this->visitObject->id_visit,  
				'uploader'=>$this->username,
				'upload_date'=>date("Y-m-d H:i:s"),  
				'acquisition_date'=>$this->studyOrthancObject->studyDate, 
				'acquisition_time'=>$this->studyOrthancObject->studyTime,
				'acquisition_datetime'=> isset($acquisitionDateTime) ? $acquisitionDateTime : null,
				'study_orthanc_id'=>$this->studyOrthancObject->studyOrthancID,
				'anon_from_orthanc_id'=>$anonFromOrthancStudyId,
				'study_uid'=>$this->studyOrthancObject->studyInstanceUID, 
				'study_description'=>$this->studyOrthancObject->studyDescription, 
				'patient_orthanc_id'=>$this->studyOrthancObject->parentPartientOrthancID,
				'patient_name'=>$this->studyOrthancObject->parentPatientName, 
				'patient_id'=>$this->studyOrthancObject->parentPatientID, 
				'number_of_series'=>$this->studyOrthancObject->numberOfSeriesInStudy,
				'number_of_instances'=>$this->studyOrthancObject->countInstances,
				'disk_size'=>$this->studyOrthancObject->diskSizeMb,
				'uncompressed_disk_size'=>$this->studyOrthancObject->uncompressedSizeMb
			));

	}
    
	/**
	 * Private function to write into the Orthanc_Series DB
	 * @param Orthanc_Serie $serie
	 */
	private function addtoDbSerie(Orthanc_Serie $serie){
        
		$serieAcquisitionDate2=$this->parseDateTime($serie->seriesDate, 1);
		$serieAcquisitionTime2=$this->parseDateTime($serie->seriesTime, 2);
        
		if($serieAcquisitionDate2!=null && $serieAcquisitionTime2!=null){
			$acquisitionDateTime=$serieAcquisitionDate2." ".$serieAcquisitionTime2;
		}
        
		$addBddSeries=$this->linkpdo->prepare('INSERT INTO orthanc_series (
                                                study_orthanc_id, 
                                                modality, 
                                                acquisition_date, 
                                                acquisition_time, 
                                                acquisition_datetime, 
                                                series_description, 
                                                injected_dose, 
                                                radiopharmaceutical, 
                                                half_life, 
                                                injected_time, 
                                                injected_activity, 
                                                series_orthanc_id, 
                                                number_of_instances, 
                                                serie_uid, 
                                                serie_number, 
                                                patient_weight, 
                                                serie_disk_size, 
                                                serie_uncompressed_disk_size, 
                                                manufacturer, 
                                                model_name, 
                                                injected_datetime) 
                                                VALUES( :Study_Orthanc_ID, 
                                                        :Modality, 
                                                        :Acquisition_Date, 
                                                        :Acquisition_Time, 
                                                        :Acquisition_DateTime, 
                                                        :Series_Description, 
                                                        :Injected_Dose, 
                                                        :Radiopharmaceutical, 
                                                        :HalfLife, 
                                                        :Injected_Time, 
                                                        :Injected_Activity,
                                                        :Series_Orthanc_ID, 
                                                        :Number_Instances, 
                                                        :Serie_UID, 
                                                        :Serie_Number, 
                                                        :Patient_Weight, 
                                                        :Serie_Disk_Size, 
                                                        :Serie_Uncompressed_Disk_Size, 
                                                        :Manufacturer, 
                                                        :Model_Name, :Injected_DateTime)
        ON DUPLICATE KEY UPDATE study_orthanc_id=:Study_Orthanc_ID, modality=:Modality, acquisition_date=:Acquisition_Date, acquisition_time=:Acquisition_Time, acquisition_datetime=:Acquisition_DateTime, series_description=:Series_Description, injected_dose=:Injected_Dose, radiopharmaceutical=:Radiopharmaceutical, half_life=:HalfLife, injected_time=:Injected_Time, injected_activity=:Injected_Activity, number_of_instances=:Number_Instances, serie_number=:Serie_Number, patient_weight=:Patient_Weight, serie_disk_size=:Serie_Disk_Size, serie_uncompressed_disk_size=:Serie_Uncompressed_Disk_Size, manufacturer=:Manufacturer, model_name=:Model_Name, deleted=0, injected_datetime=:Injected_DateTime');

		$value=array(
				'Study_Orthanc_ID'=>$this->studyOrthancObject->studyOrthancID,
				'Modality'=>$serie->modality,
				'Acquisition_Date'=>$serie->seriesDate,
				'Acquisition_Time'=>$serie->seriesTime,
				'Acquisition_DateTime'=> isset($acquisitionDateTime) ? $acquisitionDateTime : null,
				'Series_Description'=> $serie->seriesDescription,
				'Injected_Dose'=> is_numeric($serie->injectedDose) ? $serie->injectedDose : null,
				'Radiopharmaceutical'=> $serie->radiopharmaceutical,
				'HalfLife'=> is_numeric($serie->halfLife) ? $serie->halfLife : null,
				'Injected_Time'=> $serie->injectedTime,
				'Injected_DateTime'=> $this->parseDateTime($serie->injectedDateTime, 0),
				'Injected_Activity'=> is_numeric($serie->injectedActivity) ? $serie->injectedActivity: null,
				'Series_Orthanc_ID'=> $serie->serieOrthancID,
				'Number_Instances'=> $serie->numberOfInstanceInOrthanc,
				'Serie_UID'=> $serie->seriesInstanceUID,
				'Serie_Number'=> $serie->seriesNumber,
				'Patient_Weight'=>is_numeric($serie->patientWeight) ? $serie->patientWeight:null,
				'Serie_Disk_Size'=> $serie->diskSizeMb,
				'Serie_Uncompressed_Disk_Size' => $serie->uncompressedSizeMb,
				'Manufacturer'=> $serie->seriesManufacturer,
				'Model_Name'=>$serie->seriesModelName
			);
            
		$addBddSeries->execute($value);
		
	}
    
	/**
	 * Parse a DICOM date or Time string and return a string ready to send to database
	 * Return null if non parsable
	 * @param string $string
	 * @param type 0=dateTime, 1=Date, 2=Time
	 * return formated date for db saving, null if parse failed
	 */
	private function parseDateTime( $string, int $type){
		$parsedDateTime=null;
        
		//If contain time split the ms (after.) which are not constant
		if($type==0 || $type==2){
			if(strpos($string, ".")) {
				$timeWithoutms=explode(".", $string);
				$string=$timeWithoutms[0];
			}
            
		}
        
		if($type==2){
			$dateObject=DateTime::createFromFormat('His', $string);
			if($dateObject!==false){
				$parsedDateTime=$dateObject->format('H:i:s');
			}
		}else if($type==1){
			$dateObject=DateTime::createFromFormat('Ymd', $string);
			if($dateObject!==false){
				$parsedDateTime=$dateObject->format('Y-m-d');
			}
		}else if($type==0){
			$dateObject=DateTime::createFromFormat('YmdHis', $string);
			if($dateObject!==false){
				$parsedDateTime=$dateObject->format('Y-m-d H:i:s');
			}
		}
        
		return $parsedDateTime;
  
	}
    
}

