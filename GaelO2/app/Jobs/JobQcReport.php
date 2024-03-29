<?php

namespace App\Jobs;

use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\Repositories\DicomStudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\FileCacheService;
use App\GaelO\Services\GaelOProcessingService\GaelOProcessingService;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\OrthancService;
use App\Jobs\QcReport\InstanceReport;
use App\Jobs\QcReport\SeriesReport;
use App\Jobs\QcReport\VisitReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Throwable;

class JobQcReport implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private int $visitId;

    public $failOnTimeout = true;
    public $timeout = 300;
    public $tries = 1;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $visitId)
    {
        $this->onQueue('processing');
        $this->visitId = $visitId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(
        FileCacheService $fileCacheService,
        VisitRepositoryInterface $visitRepositoryInterface,
        DicomStudyRepositoryInterface $dicomStudyRepositoryInterface,
        OrthancService $orthancService,
        GaelOProcessingService $gaelOProcessingService,
    ) {
        $orthancService->setOrthancServer(true);

        $visitReport = new VisitReport();

        $visitEntity = $visitRepositoryInterface->getVisitContext($this->visitId);

        $studyName = $visitEntity['patient']['study_name'];
        $visitType = $visitEntity['visit_type']['name'];
        $patientCode = $visitEntity['patient']['code'];
        $visitDate = $visitEntity['visit_date'];
        $registrationDate = $visitEntity['patient']['registration_date'];

        $visitReport->setStudyName($studyName);
        $visitReport->setVisitName($visitType);
        $visitReport->setPatientCode($patientCode);
        $formattedVisitDate = $this->convertDate($visitDate)->format('m/d/Y');
        $visitReport->setVisitDate($formattedVisitDate);

        $dicomStudyEntity = $dicomStudyRepositoryInterface->getDicomsDataFromVisit($this->visitId, false, false);

        if ($registrationDate !== null) {
            //Determine min and max visit date compared to registration date
            $minDayToInclusion = $visitEntity['visit_type']['limit_low_days'];
            $maxDayToInclusion = $visitEntity['visit_type']['limit_up_days'];
            $formattedRegistrationDate = $this->convertDate($registrationDate)->format('m/d/Y');
            $formattedMinVisitDate = $this->convertDate($registrationDate)->modify($minDayToInclusion . ' day')->format('m/d/Y');
            $formattedMaxVisitDate = $this->convertDate($registrationDate)->modify($maxDayToInclusion . ' day')->format('m/d/Y');
            $visitReport->setRegistrationDate($formattedRegistrationDate);
            $visitReport->setMinMaxVisitDate($formattedMinVisitDate, $formattedMaxVisitDate);
        }

        $seriesReports = [];

        foreach ($dicomStudyEntity[0]['dicom_series'] as $series) {

            try {
                $seriesSharedTags = $orthancService->getSharedTags($series['orthanc_id']);
                $seriesDetails = $orthancService->getOrthancRessourcesDetails(Constants::ORTHANC_SERIES_LEVEL, $series['orthanc_id']);
                //Needed for radiopharmaceutical data (need first instance metadata to access it)
                $instanceTags = $orthancService->getInstanceTags($seriesDetails['Instances'][0]);

                $instanceReport = new InstanceReport();
                $instanceReport->fillData($instanceTags);
                $seriesReport = new SeriesReport($series['orthanc_id']);
                $seriesReport->setInstancesOrthancIds($seriesDetails['Instances']);
                $seriesReport->fillData($seriesSharedTags);
                $seriesReport->setInstanceReport($instanceReport);

                $seriesReport->loadSeriesPreview($orthancService, $gaelOProcessingService);

                $seriesReports[] = $seriesReport;
            } catch (Throwable $t) {
                Log::error($t);
            }
        }

        $visitReport->setSeriesReports($seriesReports);

        $seriesReports = $visitReport->getSeriesReports();
        foreach ($seriesReports as $seriesReport) {
            $seriesInstanceUID = $seriesReport->getSeriesInstanceUID();
            $previews = $seriesReport->getPreviews();
            for ($i = 0; $i < sizeof($previews); $i++) {
                $fileCacheService->storeSeriesPreview($seriesInstanceUID, $i, file_get_contents($previews[$i]));
            }
            $fileCacheService->storeDicomMetadata($seriesInstanceUID, json_encode($seriesReport->toArray()));
        }


        $studyInfo = $visitReport->toArray();
        $studyInstanceUID = $dicomStudyEntity[0]['study_uid'];
        $fileCacheService->storeDicomMetadata($studyInstanceUID, json_encode($studyInfo));

        //Once job finished remove preview file to avoid dangling temporary files
        foreach ($seriesReports as $seriesReport) {
            $seriesReport->deletePreviewImages();
        }
    }


    private function convertDate(string $visitDate): \DateTime
    {
        return new \DateTime($visitDate);
    }

    public function failed(Throwable $exception)
    {
        $mailServices = App::make(MailServices::class);
        $mailServices->sendJobFailure('QcReport', ['visitId' => $this->visitId], $exception->getMessage());
        if (app()->bound('sentry')) {
            app('sentry')->captureException($exception);
        }
    }
}
