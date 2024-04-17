<?php

namespace App\Console\Commands;

use App\GaelO\Services\OrthancService;
use App\Models\DicomSeries;
use App\Models\Study;
use App\Models\Visit;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DeleteVisitsOlderThan extends Command
{

    private Study $study;
    private Visit $visit;
    private DicomSeries $dicomSeries;
    private OrthancService $orthancService;
    private GaelODeleteRessourcesRepository $gaelODeleteRessourcesRepository;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gaelo:delete-visits-older-than {studyName : the study name to delete old visits} {numberOfDays : days threshold from visit creation} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete visits of a Study created later than days threshold (hard delete)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(
        Study $study,
        Visit $visit,
        DicomSeries $dicomSeries,
        OrthancService $orthancService,
        GaelODeleteRessourcesRepository $gaelODeleteRessourcesRepository
    ) {
        $this->study = $study;
        $this->visit = $visit;
        $this->dicomSeries = $dicomSeries;
        $this->orthancService = $orthancService;
        $this->gaelODeleteRessourcesRepository = $gaelODeleteRessourcesRepository;
        $this->orthancService->setOrthancServer(true);

        $studyName = $this->argument('studyName');
        $numberOfDays = $this->argument('numberOfDays');
        $force = $this->option('force');

        if (!$force) {
            $studyNameConfirmation = $this->ask('Warning : Please confirm study Name');

            if ($studyName !== $studyNameConfirmation) {
                $this->error('Wrong study name, terminating');
                return 0;
            }

            if (!$this->confirm('Warning : This CANNOT be undone, do you wish to continue?')) {
                return 0;
            }
        }

        $studyEntity = $this->study->withTrashed()->findOrFail($studyName);

        //Check that no ancillary study is remaining on this study
        $ancilariesStudies = $this->study->where('ancillary_of', $studyName)->get();
        if ($ancilariesStudies->count() > 0) {
            $this->error('Delete all ancilaries studies first');
            return 0;
        }

        if ($studyEntity['ancillary_of']) {
            $this->error('Cannot be used for an ancillary study');
            return 0;
        }

        //Get visits created more than 5 day
        $visits = $this->getOlderVisitsOfStudy($studyName, date('Y.m.d', strtotime("-" . $numberOfDays . " days")));

        $visitIds = array_map(function ($visit) {
            return $visit['id'];
        }, $visits->toArray());

        $patientIds = array_map(function ($visit) {
            return $visit['patient']['id'];
        }, $visits->toArray());

        $this->gaelODeleteRessourcesRepository->deleteReviews($visitIds, $studyName);
        $this->gaelODeleteRessourcesRepository->deleteReviewStatus($visitIds, $studyName);
        $this->gaelODeleteRessourcesRepository->deleteTrackerOfVisits($visitIds, $studyName);

        $dicomSeries = $this->getDicomSeriesOfVisits($visitIds);
        $orthancIdArray = array_map(function ($seriesId) {
            return $seriesId['orthanc_id'];
        }, $dicomSeries);

        $this->info(implode(",", $visitIds));

        $this->gaelODeleteRessourcesRepository->deleteDicomsSeries($visitIds);
        $this->gaelODeleteRessourcesRepository->deleteDicomsStudies($visitIds);
        $this->gaelODeleteRessourcesRepository->deleteVisits($visitIds);
        //Remove patients with no visits
        $this->gaelODeleteRessourcesRepository->deletePatientsWithNoVisits($patientIds);

        foreach ($orthancIdArray as $seriesOrthancId) {
            try {
                $this->info('Deleting ' . $seriesOrthancId);
                $this->orthancService->deleteFromOrthanc('series', $seriesOrthancId);
            } catch (Exception $e) {
                Log::error($e->getMessage());
            }
        }

        $this->info('The command was successful !');

        return 0;
    }

    private function getDicomSeriesOfVisits(array $visitIds)
    {
        return $this->dicomSeries
            ->whereHas('dicomStudy', function ($query) use ($visitIds) {
                $query->whereIn('visit_id', $visitIds)->withTrashed();
            })->select('orthanc_id')->get()->toArray();
    }

    private function getOlderVisitsOfStudy(string $studyName, string $datelimit)
    {
        return $this->visit->withTrashed()->whereDate('creation_date', '<=', $datelimit)->with(['visitType', 'patient'])
            ->whereHas('patient', function ($query) use ($studyName) {
                $query->where('study_name', $studyName);
            })->get();
    }
}
