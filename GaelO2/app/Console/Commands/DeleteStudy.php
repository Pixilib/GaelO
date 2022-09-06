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
use Illuminate\Console\Command;

class DeleteStudy extends Command
{

    private Study $study;
    private Visit $visit;
    private ReviewStatus $reviewStatus;
    private DicomStudy $dicomStudy;
    private DicomSeries $dicomSeries;
    private Tracker $tracker;
    private Documentation $documentation;
    private Role $role;
    private VisitGroup $visitGroup;
    private Review $review;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'study:delete {studyName : the study name to delete}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete a Study from GaelO (hard delete)';

    /**
     * Create a new command instance.
     *
     * @return void
     */
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
        parent::__construct();
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

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $studyName = $this->argument('studyName');
        $studyNameConfirmation = $this->ask('Warning : Please confirm study Name');

        if ($studyName !== $studyNameConfirmation) {
            $this->error('Wrong study name, terminating');
            return 0;
        }

        $studyEntity = $this->study->withTrashed()->findOrFail($studyName);

        //Check study have been soft delete before real deletion
        if (!$studyEntity->trashed()) {
            $this->error('Study is not soft deleted, terminating');
            return 0;
        }

        //Check that no ancillary study is remaining on this study
        $ancilariesStudies = $this->study->where('ancillary_of', $studyName)->get();
        if ($ancilariesStudies->count() > 0) {
            $this->error('Delete all ancilaries studies first');
            return 0;
        }

        if ($this->confirm('Warning : This CANNOT be undone, do you wish to continue?')) {

            $this->deleteDocumentation($studyEntity->name);
            $this->deleteRoles($studyEntity->name);
            $this->deleteTracker($studyEntity->name);
            $visits = $this->getVisitsOfStudy($studyEntity->name);



            $visitIds = array_map(function ($visit) {
                return $visit['id'];
            }, $visits->toArray());

            $dicomSeries = $this->getDicomSeriesOfVisits($visitIds);

            $this->table(
                ['orthanc_id'],
                $dicomSeries
            );

            $this->deleteDicomsSeries($visitIds);
            $this->deleteDicomsStudies($visitIds);
            $this->deleteReviews($visitIds);
            $this->deleteReviewStatus($visitIds);
            $this->deleteVisits($visitIds);
            $this->deleteVisitGroupAndVisitType($studyName);
            $this->deletePatient($studyName);
            $studyEntity->forceDelete();
            //SK TODO : Delete associated files (supprimer le repertoire de stockage de l'étude)
            //SK TODO : Faire Deletion auto des ressources dans Orthanc?
            $this->info('The command was successful, delete Orthanc Series and Associated Form Data !');
        }

        return 0;
    }

    private function getDicomSeriesOfVisits(array $visitIds)
    {
        return $this->dicomSeries
            ->whereHas('dicomStudy', function ($query) use ($visitIds) {
                $query->whereIn('visit_id', $visitIds)->withTrashed();
            })->select('orthanc_id')->get()->toArray();
    }

    private function getVisitsOfStudy(string $studyName)
    {
        return $this->visit->withTrashed()->with(['visitType', 'patient'])
            ->whereHas('patient', function ($query) use ($studyName) {
                $query->where('study_name', $studyName);
            })->get();
    }

    private function deleteDocumentation(string $studyName)
    {
        $this->documentation->where('study_name', $studyName)->withTrashed()->forceDelete();
    }

    private function deleteRoles(string $studyName)
    {
        $this->role->where('study_name', $studyName)->delete();
    }

    private function deleteTracker(string $studyName)
    {
        $this->tracker->where('study_name', $studyName)->delete();
    }

    private function deleteDicomsStudies(array $visitId)
    {
        return $this->dicomStudy->whereIn('visit_id', $visitId)->withTrashed()->forceDelete();
    }

    private function deleteDicomsSeries(array $visitId)
    {
        return $this->dicomSeries->whereHas('dicomStudy', function ($query) use ($visitId) {
            $query->whereIn('visit_id', $visitId)->withTrashed();
        })->withTrashed()->forceDelete();
    }


    private function deleteVisitGroupAndVisitType(string $studyName)
    {
        $visitGroups = $this->visitGroup->where('study_name', $studyName)->get();
        foreach ($visitGroups as $visitGroup) {
            $visitGroup->visitTypes()->delete();
            $visitGroup->delete();
        }
    }

    private function deletePatient(string $studyName)
    {
        $this->patient->where('study_name', $studyName)->delete();
    }

    private function deleteReviews(array $visitIds)
    {
        $this->review->whereIn('visit_id', $visitIds)->withTrashed()->forceDelete();
    }

    private function deleteReviewStatus(array $visitIds)
    {
        $this->reviewStatus->whereIn('id', $visitIds)->delete();
    }

    private function deleteVisits(array $visitIds)
    {
        $this->visit->whereIn('id', $visitIds)->withTrashed()->forceDelete();
    }
}
