<?php

namespace App\Console\Commands;

use App\Models\DicomSeries;
use App\Models\DicomStudy;
use App\Models\Documentation;
use App\Models\Patient;
use App\Models\Review;
use App\Models\ReviewStatus;
use App\Models\Role;
use App\Models\Study;
use App\Models\Tracker;
use App\Models\Visit;
use App\Models\VisitGroup;

class GaelODeleteRessourcesRepository
{

    private Study $study;
    private Patient $patient;
    private Visit $visit;
    private ReviewStatus $reviewStatus;
    private DicomStudy $dicomStudy;
    private DicomSeries $dicomSeries;
    private Tracker $tracker;
    private Documentation $documentation;
    private Role $role;
    private VisitGroup $visitGroup;
    private Review $review;

    public function __construct(
        Study $study,
        VisitGroup $visitGroup,
        Visit $visit,
        Patient $patient,
        DicomStudy $dicomStudy,
        DicomSeries $dicomSeries,
        Role $role,
        Tracker $tracker,
        Documentation $documentation,
        Review $review,
        ReviewStatus $reviewStatus
    ) {

        $this->study = $study;
        $this->visitGroup = $visitGroup;
        $this->visit = $visit;
        $this->patient = $patient;
        $this->dicomStudy = $dicomStudy;
        $this->dicomSeries = $dicomSeries;
        $this->tracker = $tracker;
        $this->documentation = $documentation;
        $this->role = $role;
        $this->review = $review;
        $this->reviewStatus = $reviewStatus;
    }

    public function deleteDocumentation(string $studyName)
    {
        $this->documentation->where('study_name', $studyName)->withTrashed()->forceDelete();
    }

    public function deleteRoles(string $studyName)
    {
        $this->role->where('study_name',  $studyName)->delete();
    }

    public function deleteTracker(string $studyName)
    {
        $this->tracker->where('study_name', $studyName)->delete();
    }

    public function deleteTrackerOfVisits(array $visitIds, string $studyName)
    {
        $this->tracker->where('study_name', $studyName)->whereIn('visit_id', $visitIds)->delete();
    }

    public function deleteDicomsStudies(array $visitId)
    {
        return $this->dicomStudy->whereIn('visit_id', $visitId)->withTrashed()->forceDelete();
    }

    public function deleteDicomsSeries(array $visitId)
    {
        return $this->dicomSeries->whereHas('dicomStudy', function ($query) use ($visitId) {
            $query->whereIn('visit_id', $visitId)->withTrashed();
        })->withTrashed()->forceDelete();
    }

    public function deletePatient(string $studyName)
    {
        $this->patient->where('study_name', $studyName)->delete();
    }

    public function deletePatientsWithNoVisits(string $studyName)
    {
        $this->patient->where('study_name', $studyName)->doesntHave('visits')->delete();
    }

    public function deleteReviews(array $visitIds, string $studyName)
    {
        $this->review->where('study_name', $studyName)->whereIn('visit_id', $visitIds)->withTrashed()->forceDelete();
    }

    public function deleteReviewStatus(array $visitIds, String $studyName)
    {
        $this->reviewStatus->where('study_name', $studyName)->whereIn('visit_id', $visitIds)->delete();
    }

    public function deleteStudy(string $studyName)
    {
        $studyEntity = $this->study->withTrashed()->findOrFail($studyName);
        $studyEntity->forceDelete();
    }

    public function deleteVisits(array $visitIds)
    {
        $this->visit->whereIn('id', $visitIds)->withTrashed()->forceDelete();
    }

    public function deleteVisitGroupAndVisitType(string $studyName)
    {
        $visitGroups = $this->visitGroup->where('study_name', $studyName)->get();
        foreach ($visitGroups as $visitGroup) {
            $visitGroup->visitTypes()->delete();
            $visitGroup->delete();
        }
    }
}
