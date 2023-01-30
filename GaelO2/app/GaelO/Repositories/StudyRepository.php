<?php

namespace App\GaelO\Repositories;

use App\GaelO\Entities\StudyEntity;
use App\Models\Study;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;

class StudyRepository implements StudyRepositoryInterface
{

    private Study $studyModel;

    public function __construct(Study $study)
    {
        $this->studyModel = $study;
    }

    public function find($name): StudyEntity
    {
        $studyInfoArray = $this->studyModel->findOrFail($name)->toArray();
        return StudyEntity::fillFromDBReponseArray($studyInfoArray);
    }

    public function delete($name): void
    {
        $this->studyModel->findOrFail($name)->delete();
    }

    public function addStudy(String $name, string $code, int $patientCodeLength, string $contactEmail, bool $controllerShowAll, bool $monitorShowAll, bool $documentationMandatory, ?string $ancillaryOf): void
    {
        $study = new Study();
        $study->name = $name;
        $study->code = $code;
        $study->patient_code_length = $patientCodeLength;
        $study->contact_email = $contactEmail;
        $study->controller_show_all = $controllerShowAll;
        $study->monitor_show_all = $monitorShowAll;
        $study->documentation_mandatory = $documentationMandatory;
        $study->ancillary_of = $ancillaryOf;
        $study->save();
    }

    public function isExistingStudyName(string $name): bool
    {
        $studies = $this->studyModel->withTrashed()->where('name', $name)->get();
        return $studies->count() > 0 ? true : false;
    }

    public function isExistingStudyCode(string $code): bool
    {
        $studies = $this->studyModel->withTrashed()->where('code', $code)->get();
        return $studies->count() > 0 ? true : false;
    }

    public function getStudies(bool $withTrashed = false): array
    {
        if ($withTrashed) {
            $studies = $this->studyModel->withTrashed()->get();
        } else {
            $studies = $this->studyModel->get();
        }
        return $studies->count() == 0 ? [] : $studies->toArray();
    }

    public function getAncillariesStudyOfStudy(string $studyName): array
    {
        $ancilariesStudies = $this->studyModel->where('ancillary_of', $studyName)->get();
        return $ancilariesStudies->count() == 0 ? [] : $ancilariesStudies->pluck('name')->toArray();
    }

    public function getStudyWithDetails(string $name): array
    {
        $studiesDetails = $this->studyModel->with('visitGroups', 'visitGroups.visitTypes')->findOrFail($name);
        return $studiesDetails->toArray();
    }

    public function getAllStudiesWithDetails(): array
    {
        $studiesDetails = $this->studyModel->withTrashed()->with('visitGroups', 'visitGroups.visitTypes')->get();
        return $studiesDetails->toArray();
    }

    public function reactivateStudy(string $name): void
    {
        $this->studyModel->withTrashed()->findOrFail($name)->restore();
    }

    public function getStudyStatistics(string $name): array
    {
        $counts = $this->studyModel::withCount(['patients', 'visits', 'dicomStudies', 'dicomSeries'])->where('name', $name)->sole()->toArray();
        $counts['dicom_instances_count'] = $this->studyModel->findOrFail($name)->dicomStudies()->sum('number_of_instances');
        $counts['dicom_disk_size'] = $this->studyModel->findOrFail($name)->dicomStudies()->sum('disk_size');
        return $counts;
    }
}
