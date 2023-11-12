<?php

namespace App\Console\Commands;

use App\GaelO\Services\OrthancService;
use App\Models\DicomSeries;
use App\Models\Study;
use App\Models\Visit;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DeleteStudy extends Command
{

    private Study $study;
    private Visit $visit;
    private DicomSeries $dicomSeries;
    private OrthancService $orthancService;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gaelo:delete-study {studyName : the study name to delete} {--deleteDicom : delete dicom in Orthanc} { --deleteAssociatedFile : delete associated files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete a Study from GaelO (hard delete)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(
        Study $study,
        Visit $visit,
        DicomSeries $dicomSeries,
        OrthancService $orthancService
    ) {
        $this->study = $study;
        $this->visit = $visit;
        $this->dicomSeries = $dicomSeries;
        $this->orthancService = $orthancService;
        $this->orthancService->setOrthancServer(true);

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

            $gaeloDeleteRessourceService = App::make(GaelODeleteRessourcesService::class);
            $gaeloDeleteRessourceService->deleteDocumentation($studyEntity->name);
            $gaeloDeleteRessourceService->deleteRoles($studyEntity->name);
            $gaeloDeleteRessourceService->deleteTracker($studyEntity->name);

            //Get Visit ID of Original Study
            $originalStudy = $studyEntity->ancillary_of ?? $studyEntity->name;
            $visits = $this->getVisitsOfStudy($originalStudy);

            $visitIds = array_map(function ($visit) {
                return $visit['id'];
            }, $visits->toArray());

            $gaeloDeleteRessourceService->deleteReviews($visitIds, $studyName);
            $gaeloDeleteRessourceService->deleteReviewStatus($visitIds, $studyName);

            if ($studyEntity['ancillary_of'] === null) {

                $dicomSeries = $this->getDicomSeriesOfVisits($visitIds);

                $orthancIdArray = array_map(function ($seriesId) {
                    return $seriesId['orthanc_id'];
                }, $dicomSeries);
    
                $this->line(implode(" ", $orthancIdArray));
    
                $this->table(
                    ['orthanc_id'],
                    $dicomSeries
                );

                $gaeloDeleteRessourceService->deleteDicomsSeries($visitIds);
                $gaeloDeleteRessourceService->deleteDicomsStudies($visitIds);
                $gaeloDeleteRessourceService->deleteVisits($visitIds);
                $gaeloDeleteRessourceService->deleteVisitGroupAndVisitType($studyName);
                $gaeloDeleteRessourceService->deletePatient($studyName);
            }

            $studyEntity->forceDelete();

            if ($this->option('deleteDicom') && $this->confirm('Found ' . sizeOf($orthancIdArray) . ' series to delete, do you want to continue ?')) {
                foreach ($orthancIdArray as $seriesOrthancId) {
                    try {
                        $this->orthancService->deleteFromOrthanc('series', $seriesOrthancId);
                    } catch (Exception $e) {
                        Log::error($e->getMessage());
                    }
                }
            }

            if ($this->option('deleteAssociatedFile') && $this->confirm('Going to delete associated file, do you want to continue ?')) {
                Storage::deleteDirectory($studyName);
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
