<?php

namespace App\GaelO\Repositories;

use App\GaelO\Interfaces\Repositories\DicomStudyRepositoryInterface;
use App\Models\DicomStudy;

class DicomStudyRepository implements DicomStudyRepositoryInterface
{

    private DicomStudy $dicomStudyModel;

    public function __construct(DicomStudy $dicomStudy)
    {
        $this->dicomStudyModel = $dicomStudy;
    }

    public function delete($studyInstanceUID): void
    {
        $this->dicomStudyModel->findOrFail($studyInstanceUID)->delete();
    }

    public function reactivateByStudyInstanceUID(string $studyInstanceUID): void
    {
        $this->dicomStudyModel->withTrashed()->where('study_uid', $studyInstanceUID)->sole()->restore();
    }

    public function addStudy(
        string $orthancStudyID,
        int $visitID,
        int $uploaderID,
        string $uploadDate,
        ?string $acquisitionDate,
        ?string $acquisitionTime,
        string $anonFromOrthancID,
        string $studyUID,
        ?string $studyDescription,
        string $patientOrthancID,
        ?string $patientName,
        ?string $patientID,
        int $numberOfSeries,
        int $numberOfInstance,
        int $diskSize,
        int $uncompressedDisksize
    ): void {

        $dicomStudy = new DicomStudy();

        $dicomStudy->orthanc_id = $orthancStudyID;
        $dicomStudy->visit_id = $visitID;
        $dicomStudy->user_id = $uploaderID;
        $dicomStudy->upload_date = $uploadDate;
        $dicomStudy->acquisition_date = $acquisitionDate;
        $dicomStudy->acquisition_time = $acquisitionTime;
        $dicomStudy->anon_from_orthanc_id = $anonFromOrthancID;
        $dicomStudy->study_uid = $studyUID;
        $dicomStudy->study_description = $studyDescription;
        $dicomStudy->patient_orthanc_id = $patientOrthancID;
        $dicomStudy->patient_name = $patientName;
        $dicomStudy->patient_id = $patientID;
        $dicomStudy->number_of_series = $numberOfSeries;
        $dicomStudy->number_of_instances = $numberOfInstance;
        $dicomStudy->disk_size = $diskSize;
        $dicomStudy->uncompressed_disk_size = $uncompressedDisksize;

        $dicomStudy->save();
    }

    /**
     * Check that for a study the original Orthanc Id (StudyUID Hash) is not existing
     * This is done per study as a imaging procedure can be included in different trial
     */
    public function isExistingOriginalOrthancStudyID(string $originalOrthancStudyID, string $studyName): bool
    {
        $dicomStudies = $this->dicomStudyModel->where('anon_from_orthanc_id', $originalOrthancStudyID)
            ->whereHas('visit', function ($query) use ($studyName) {
                $query->whereHas('patient', function ($query) use ($studyName) {
                    $query->where('study_name', $studyName);
                });
            })
            ->get();

        return $dicomStudies->count() > 0 ? true : false;
    }

    public function getStudyInstanceUidFromVisit(int $visitID): string
    {
        $studyEntity = $this->dicomStudyModel->where('visit_id', '=', $visitID)->sole();
        return $studyEntity->study_uid;
    }

    public function isExistingDicomStudyForVisit(int $visitID): bool
    {
        $dicomStudies =  $this->dicomStudyModel->where('visit_id', $visitID)->get();
        return $dicomStudies->count() > 0 ? true : false;
    }

    public function getDicomsDataFromVisit(int $visitID, bool $withDeletedStudy, bool $withDeletedSeries): array
    {
        $query = $this->dicomStudyModel->where('visit_id', $visitID)->with('uploader');

        if ($withDeletedStudy) $query = $query->withTrashed();

        if ($withDeletedSeries) {
            $query = $query->with(['dicomSeries' => function ($query) {
                $query->withTrashed();
            }]);
        } else {
            $query = $query->with('dicomSeries');
        }

        $studies = $query->get();
        return $studies->count() == 0 ? [] : $studies->toArray();
    }

    public function getDicomStudy(string $studyInstanceUID, bool $includeDeleted): array
    {
        if ($includeDeleted) {
            $study = $this->dicomStudyModel->withTrashed()->findOrFail($studyInstanceUID)->toArray();
        } else {
            $study = $this->dicomStudyModel->findOrFail($studyInstanceUID)->toArray();
        }

        return $study;
    }

    public function getChildSeries(string $studyInstanceUID, bool $onlyTrashed): array
    {
        $query = $this->dicomStudyModel->findOrFail($studyInstanceUID)->dicomSeries();
        if ($onlyTrashed) $query->onlyTrashed();

        return $query->get()->toArray();
    }

    public function getDicomStudyFromVisitIdArray(array $visitId, bool $withTrashed): array
    {
        $queryBuilder = $this->dicomStudyModel->whereIn('visit_id', $visitId);

        if ($withTrashed) $queryBuilder->withTrashed();

        $answer = $queryBuilder->get();
        return $answer->count() === 0 ? []  : $answer->toArray();
    }

    public function getDicomStudyFromVisitIdArrayWithSeries(array $visitId, bool $withTrashed): array
    {

        $queryBuilder = $this->dicomStudyModel
            ->with(['visit' => function ($query) {
                $query->with(['visitType', 'visitType.visitGroup', 'patient']);
            }])
            ->with(['dicomSeries' => function ($query) use ($withTrashed) {
                if ($withTrashed) $query->withTrashed();
            }])
            ->whereIn('visit_id', $visitId);

        if ($withTrashed) $queryBuilder->withTrashed();

        $answer = $queryBuilder->get();

        return $answer->count() === 0 ? []  : $answer->toArray();
    }
}
