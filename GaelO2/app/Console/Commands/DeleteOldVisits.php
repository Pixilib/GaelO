<?php

namespace App\Console\Commands;

use App\GaelO\Services\OrthancService;
use App\Models\DicomSeries;
use App\Models\Study;
use App\Models\Visit;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DeleteOldVisits extends Command
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
    protected $signature = 'gaelo:delete-old-visits {studyName : the study name to delete old visits} {numberOfDays : days threshold from visit creation}';

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
        $studyNameConfirmation = $this->ask('Warning : Please confirm study Name');

        if ($studyName !== $studyNameConfirmation) {
            $this->error('Wrong study name, terminating');
            return 0;
        }

        $studyEntity = $this->study->withTrashed()->findOrFail($studyName);
        
        //Check that no ancillary study is remaining on this study
        $ancilariesStudies = $this->study->where('ancillary_of', $studyName)->get();
        if ($ancilariesStudies->count() > 0) {
            $this->error('Delete all ancilaries studies first');
            return 0;
        }

        if($studyEntity['ancillary_of']){
            $this->error('Cannot be used for an ancillary study');
            return 0;
        }

        if ($this->confirm('Warning : This CANNOT be undone, do you wish to continue?')) {

            //TODO delete tracker doit etre specifique au visites supprimées
            //$this->gaelODeleteRessourcesRepository->deleteTracker($studyEntity->name);

            //Get Visit ID of Original Study
            //TODO Doit tenir compte de l'interval de temps depuis la creation
            $visits = $this->getVisitsOfStudy($studyName);

            $visitIds = array_map(function ($visit) {
                return $visit['id'];
            }, $visits->toArray());

            $this->gaelODeleteRessourcesRepository->deleteReviews($visitIds, $studyName);
            $this->gaelODeleteRessourcesRepository->deleteReviewStatus($visitIds, $studyName);

            $dicomSeries = $this->getDicomSeriesOfVisits($visitIds);
            $orthancIdArray = array_map(function ($seriesId) {
                return $seriesId['orthanc_id'];
            }, $dicomSeries);

            $this->line(implode(" ", $orthancIdArray));

            $this->table(
                ['orthanc_id'],
                $dicomSeries
            );

            $this->gaelODeleteRessourcesRepository->deleteDicomsSeries($visitIds);
            $this->gaelODeleteRessourcesRepository->deleteDicomsStudies($visitIds);
            $this->gaelODeleteRessourcesRepository->deleteVisits($visitIds);
            //TODO Doit supprimer les patients que si il ne reste pas de visites...
            //$this->gaelODeleteRessourcesRepository->deletePatient($studyName);
            

            if ($this->option('deleteDicom') && $this->confirm('Found ' . sizeOf($orthancIdArray) . ' series to delete, do you want to continue ?')) {
                foreach ($orthancIdArray as $seriesOrthancId) {
                    try {
                        $this->info('Deleting '.$seriesOrthancId);
                        $this->orthancService->deleteFromOrthanc('series', $seriesOrthancId);
                    } catch (Exception $e) {
                        Log::error($e->getMessage());
                    }
                }
            }

            if ($this->option('deleteAssociatedFile') && $this->confirm('Going to delete associated file, do you want to continue ?')) {
                //TODO doit supprimer que les associated file des visites deleted
                //Storage::deleteDirectory($studyName);
            }

            $this->info('The command was successful !');
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

}
