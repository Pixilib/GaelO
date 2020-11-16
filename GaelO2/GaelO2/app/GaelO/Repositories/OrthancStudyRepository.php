<?php

namespace App\GaelO\Repositories;

use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Util;
use App\OrthancStudy;

class OrthancStudyRepository implements PersistenceInterface{


    public function __construct(OrthancStudy $orthancStudy){
        $this->orthancStudy = $orthancStudy;
    }

    public function create(array $data){
        $orthancStudy = new OrthancStudy();
        $model = Util::fillObject($data, $orthancStudy);
        $model->save();
    }

    public function update($orthancStudyID, array $data) : void {
        $model = $this->orthancStudy->find($orthancStudyID);
        $model = Util::fillObject($data, $model);
        $model->save();
    }

    public function find($orthancStudyID){
        return $this->orthancStudy->where('orthanc_id', $orthancStudyID)->firstOrFail()->toArray();
    }

    public function delete($orthancStudyID) :void {
        $this->orthancStudy->find($orthancStudyID)->delete();
    }

    public function getAll() : array {
        throw new \Exception('Not Usable in Orthanc Study Repository');
    }

    public function addStudy(string $orthancStudyID, int $visitID, int $uploaderID, string $uploadDate,
                    ?string $acquisitionDate, ?string $acquisitionTime, string $anonFromOrthancID,
                    string $studyUID, ?string $studyDescription, string $patientOrthancID,
                    ?string $patientName, ?string $patientID, int $numberOfSeries, int $numberOfInstance,
                    int $diskSize, int $uncompressedDisksize  ){
        $data = [
            'orthanc_id' => $orthancStudyID,
            'visit_id' => $visitID,
            'uploader_id' => $uploaderID,
            'upload_date' => $uploadDate,
            'acquisition_date' => $acquisitionDate,
            'acquisition_time' => $acquisitionTime,
            'anon_from_orthanc_id' => $anonFromOrthancID,
            'study_uid' => $studyUID,
            'study_description' => $studyDescription,
            'patient_orthanc_id' => $patientOrthancID,
            'patient_name' => $patientName,
            'patient_id' => $patientID,
            'number_of_series' => $numberOfSeries,
            'number_of_instances' => $numberOfInstance,
            'disk_size' => $diskSize,
            'uncompressed_disk_size' => $uncompressedDisksize

        ];

        $this->create($data);

    }

    public function updateStudy(string $orthancStudyID, int $visitID, int $uploaderID, string $uploadDate,
                                ?string $acquisitionDate, ?string $acquisitionTime, string $anonFromOrthancID,
                                string $studyUID, ?string $studyDescription, string $patientOrthancID,
                                ?string $patientName, ?string $patientID, int $numberOfSeries, int $numberOfInstance,
                                int $diskSize, int $uncompressedDisksize ){

        $data = [
            'visit_id' => $visitID,
            'uploader_id' => $uploaderID,
            'upload_date' => $uploadDate,
            'acquisition_date' => $acquisitionDate,
            'acquisition_time' => $acquisitionTime,
            'anon_from_orthanc_id' => $anonFromOrthancID,
            'study_uid' => $studyUID,
            'study_description' => $studyDescription,
            'patient_orthanc_id' => $patientOrthancID,
            'patient_name' => $patientName,
            'patient_id' => $patientID,
            'number_of_series' => $numberOfSeries,
            'number_of_instances' => $numberOfInstance,
            'disk_size' => $diskSize,
            'uncompressed_disk_size' => $uncompressedDisksize

        ];

        $this->update($orthancStudyID, $data);


    }

    public function isExistingOriginalOrthancStudyID(string $orthancStudyID){
        $orthancStudies = $this->orthancStudy->where('anon_from_orthanc_id', $orthancStudyID)->get();
        return $orthancStudies->count()>0 ? true : false;
    }

    public function isExistingOrthancStudyID(string $orthancStudyID){
        $orthancStudies = $this->orthancStudy->where('orthanc_id',$orthancStudyID);
        return $orthancStudies->count()>0 ? true : false;
    }
}
