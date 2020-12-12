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
        return $this->orthancStudy->findOrFail($orthancStudyID)->toArray();
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

    /**
     * Check that for a study the original Orthanc Id (StudyUID Hash) is not existing
     * This is done per study as a imaging procedure can be included in different trial
     */
    public function isExistingOriginalOrthancStudyID(string $orthancStudyID, string $studyName){
        $orthancStudies = $this->orthancStudy->where('anon_from_orthanc_id', $orthancStudyID)
                                                        ->join('visits', function ($join) {
                                                            $join->on('orthanc_studies.visit_id', '=', 'visits.id');
                                                        })->join('visit_types', function ($join) {
                                                            $join->on('visit_types.id', '=', 'visits.visit_type_id');
                                                        })->join('visit_groups', function ($join) {
                                                            $join->on('visit_groups.id', '=', 'visit_types.visit_group_id');
                                                        })->where(function ($query) use ($studyName) {
                                                            $query->where('visit_groups.study_name', '=', $studyName);
                                                        })->get();
        return $orthancStudies->count()>0 ? true : false;
    }

    /**
     * Check that OrthancStudyID is not existing.
     * OrthancStudyID is a primary key, even if a same original study is included in two trials the ID will be
     * Generated by anonymization
     */
    public function isExistingOrthancStudyID(string $orthancStudyID){
        $orthancStudies = $this->orthancStudy->where('orthanc_id',$orthancStudyID);
        return $orthancStudies->count()>0 ? true : false;
    }

    public function getStudyOrthancIDFromVisit(int $visitID) : string {
        return $this->orthancStudy->where('visit_id', $visitID)->firstOrFail()->value('orthanc_id');
    }

    public function getOrthancStudyByStudyInstanceUID(string $studyInstanceUID, bool $includeDeleted) : array {
        if($includeDeleted){
            $study = $this->orthancStudy->where('study_uid',$studyInstanceUID)->withTrashed()->firstOrFail()->toArray();
        }else{
            $study = $this->orthancStudy->where('study_uid',$studyInstanceUID)->firstOrFail()->toArray();
        }

        return $study;

    }

    public function getChildSeries(string $orthancStudyID, bool $deleted) : array {
        if($deleted === false){
            $series = $this->orthancStudy->where('orthanc_id',$orthancStudyID)->first()->series()->get()->toArray();
        }else{
            $series = $this->orthancStudy->where('orthanc_id',$orthancStudyID)->first()->series()->onlyTrashed()->get()->toArray();
        }

        return $series;

    }

}
