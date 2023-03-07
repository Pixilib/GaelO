<?php

namespace App\Jobs\QcReport;

use App\GaelO\Constants\Constants;
use App\GaelO\Constants\Enums\InvestigatorFormStateEnum;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Repositories\DicomStudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\OrthancService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class JobQcReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private int $visitId;
    private OrthancService $orthancService;

    public $failOnTimeout = true;
    public $timeout = 600;
    public $backoff = 60;
    public $tries = 1;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $visitId)
    {
        $this->onQueue('auto-qc');
        $this->visitId = $visitId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(
        FrameworkInterface $frameworkInterface,
        UserRepositoryInterface $userRepositoryInterface,
        VisitRepositoryInterface $visitRepositoryInterface,
        DicomStudyRepositoryInterface $dicomStudyRepositoryInterface,
        MailServices $mailServices,
        OrthancService $orthancService,
        ReviewRepositoryInterface $reviewRepositoryInterface
    ) {
        $this->orthancService = $orthancService;
        $this->orthancService->setOrthancServer(true);

        $visitReport = new VisitReport();

        $visitEntity = $visitRepositoryInterface->getVisitContext($this->visitId);

        $studyName = $visitEntity['patient']['study_name'];
        $visitId = $visitEntity['id'];
        $visitType = $visitEntity['visit_type']['name'];
        $patientCode = $visitEntity['patient']['code'];
        $stateInvestigatorForm = $visitEntity['state_investigator_form'];
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

        if ($stateInvestigatorForm !== InvestigatorFormStateEnum::NOT_NEEDED->value) {
            $reviewEntity = $reviewRepositoryInterface->getInvestigatorForm($this->visitId, false);
            $visitReport->setInvestigatorForm($reviewEntity['review_data']);
        }

        $seriesReports = [];

        foreach ($dicomStudyEntity[0]['dicom_series'] as $series) {

            try {
                $seriesSharedTags = $this->orthancService->getSharedTags($series['orthanc_id']);
                $seriesDetails = $this->orthancService->getOrthancRessourcesDetails(Constants::ORTHANC_SERIES_LEVEL, $series['orthanc_id']);
                //Needed for radiopharmaceutical data (need first instance metadata to access it)
                $instanceTags = $this->orthancService->getInstanceTags($seriesDetails['Instances'][0]);

                $instanceReport = new InstanceReport();
                $instanceReport->fillData($instanceTags);
                $seriesReport = new SeriesReport();
                $seriesReport->fillData($seriesSharedTags);
                $seriesReport->setInstanceReport($instanceReport);
                $seriesReport->setNumberOfInstances(sizeof($seriesDetails['Instances']));

                $imagePreviewPath = $this->getSeriesPreview($seriesReport->getPreviewType(), $series['orthanc_id'], $seriesDetails['Instances'][0]);
                $seriesReport->setPreviewImagePath($imagePreviewPath);

                $seriesReports[] = $seriesReport;
            } catch (Throwable $t) {
                Log::info($t);
            }
        }

        $visitReport->setSeriesReports($seriesReports);

        $seriesReports = $visitReport->getSeriesReports();
        $seriesInfo = array_map(function (SeriesReport $seriesReport) {
            return $seriesReport->toArray();
        }, $seriesReports);

        $studyInfo = $visitReport->toArray();

        $controllerUsers = $userRepositoryInterface->getUsersByRolesInStudy($studyName, Constants::ROLE_CONTROLLER);

        foreach ($controllerUsers as $user) {
            $redirectLink = '/magic-link-tools/auto-qc';
            $queryParams = [
                'visitId' => $visitId,
                'accepted' => 'true',
                'studyName' => $studyName
            ];
            $magicLinkAccepted = $frameworkInterface->createMagicLink($user['id'], $redirectLink, $queryParams);
            $queryParams['accepted'] = 'false';
            $magicLinkRefused = $frameworkInterface->createMagicLink($user['id'], $redirectLink, $queryParams);
            $mailServices->sendQcReport($studyName, $visitType, $patientCode, $studyInfo, $seriesInfo, $magicLinkAccepted, $magicLinkRefused, $user['email']);
        }

        //TODO Comme l'envoi des mail est synchrone, suppression fichier image devrait etre OK
    }

    private function getSeriesPreview(ImageType $imageType, string $seriesID, string $firstInstanceID): ?string
    {
        try {
            $imagePath = null;
            switch ($imageType) {
                case ImageType::MIP:
                    //$imagePath = $this->orthancService->getSeriesMIP($seriesID);
                    $imagePath = $this->orthancService->getSeriesMosaic($seriesID);
                    break;
                case ImageType::MOSAIC:
                    $imagePath = $this->orthancService->getSeriesMosaic($seriesID);
                    break;
                case ImageType::DEFAULT:
                    $imagePath = $this->orthancService->getInstancePreview($firstInstanceID);
                    break;
            }
            return $imagePath;
        } catch (Throwable $t) {
            return public_path('static/media/ban-image-photo-icon.png');
        }
    }

    private function convertDate(string $visitDate): \DateTime
    {
        return new \DateTime($visitDate);
    }

    public function failed(Throwable $exception)
    {
        Log::info($exception);
    }
}