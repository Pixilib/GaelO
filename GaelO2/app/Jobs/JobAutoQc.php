<?php

namespace App\Jobs;

use App\GaelO\Constants\Constants;
use App\GaelO\Entities\DicomStudyEntity;
use App\GaelO\Entities\VisitEntity;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Repositories\DicomStudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Repositories\VisitRepository;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\OrthancService;
use App\Models\Visit;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\GaelO\Services\StoreObjects\SharedTags;

class JobAutoQc implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private int $visitId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $visitId)
    {
        $this->visitId = $visitId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(FrameworkInterface $frameworkInterface, UserRepositoryInterface $userRepositoryInterface, VisitRepositoryInterface $visitRepositoryInterface, DicomStudyRepositoryInterface $dicomStudyRepositoryInterface, 
        MailServices $mailServices, OrthancService $orthancService)
    {
        $visitEntity = $visitRepositoryInterface->getVisitContext($this->visitId);
        $dicomStudyEntity = $dicomStudyRepositoryInterface->getDicomsDataFromVisit($this->visitId, false, false);
        $sharedTags = new SharedTags($orthancService->getSharedTags($this->visitId));

        $studyInfo = [];
        $studyInfo['studyName'] = $visitEntity['patient']['study_name'];
        $studyInfo['studyDescription'] = $sharedTags->getStudyDescription();
        $studyInfo['studyManufacturer'] = $sharedTags->getStudyManufacturer();
        $studyInfo['studyDate'] = $sharedTags->getStudyDate();
        $studyInfo['studyTime'] = $sharedTags->getStudyTime();
        $studyInfo['numberOfSeries'] = count($dicomStudyEntity[0]['dicom_series']);
        $studyInfo['numberOfInstances'] = 0;

        $seriesInfo = [];
        foreach ($dicomStudyEntity[0]['dicom_series'] as $series) {
            $studyInfo['numberOfInstances'] += $series['number_of_instances'];

            $seriesData = [];
            $seriesData['infos'] = [];
            $seriesSharedTags = new SharedTags($orthancService->getSharedTags($series['orthanc_id']));
            if ($sharedTags->getImageType() == 0) {
                $seriesData['image_path'] = $orthancService->getMosaic($series['orthanc_id']);
            } else if ($sharedTags->getImageType() == 1) {
                $seriesData['image_path'] = $orthancService->getMIP($series['orthanc_id']);
            } else {
                $seriesData['image_path'] = $orthancService->getPreview($series['orthanc_id']);
            }
            $seriesData['series_description'] = $seriesSharedTags->getSeriesDescription();
            $seriesData['infos']['modality'] = $seriesSharedTags->getSeriesModality();
            $seriesData['infos']['series_date'] =  $seriesSharedTags->getSeriesDate();
            $seriesData['infos']['series_time'] = $series['acquisition_time'];
            $seriesData['infos']['slice_thickness'] = $seriesSharedTags->getSliceThickness();
            $seriesData['infos']['pixel_spacing'] = $seriesSharedTags->getPixelSpacing();
            $seriesData['infos']['fov'] = $seriesSharedTags->getFieldOfView();
            $seriesData['infos']['matrix_size'] = $seriesSharedTags->getMatrixSize();
            $seriesData['infos']['patient_position'] = $seriesSharedTags->getPatientPosition();
            $seriesData['infos']['patient_orientation'] = $seriesSharedTags->getImageOrientation();
            $seriesData['infos']['number_of_instances'] = $series['number_of_instances'];
            if ($seriesData['infos']['modality'] == 'MR') {
                $seriesData['infos']['scanning_sequence'] = $seriesSharedTags->getScanningSequence();
                $seriesData['infos']['sequence_variant'] = $seriesSharedTags->getSequenceVariant();
                $seriesData['infos']['echo_time'] = $seriesSharedTags->getEchoTime();
                $seriesData['infos']['inversion_time'] = $seriesSharedTags->getInversionTime();
                $seriesData['infos']['echo_train_length'] = $seriesSharedTags->getEchoTrainLength();
                $seriesData['infos']['spacing_between_slices'] = $seriesSharedTags->getSpacingBetweenSlices();
                $seriesData['infos']['protocol_name'] = $seriesSharedTags->getProtocolName();
            } else if ($seriesData['infos']['modality'] == 'PT') {
                $seriesData['infos']['patient_weight'] = $seriesSharedTags->getPatientWeight();
                $seriesData['infos']['patient_height'] = $seriesSharedTags->getPatientHeight();
                $seriesData['infos'][] = [$seriesData['infos'], ...$seriesSharedTags->getRadioPharmaceuticalTags($orthancService,
                    $series['dicom_instances'][0]['orthanc_id'])];
            }
            $seriesInfo[] = $seriesData;
        }

        $studyName = $visitEntity['patient']['study_name'];
        $visitId = $visitEntity['id'];
        $visitType = $visitEntity['visit_type']['name'];
        $patientCode = $visitEntity['patient']['code'];

        $controllerUsers = $userRepositoryInterface->getUsersByRolesInStudy($studyName, Constants::ROLE_CONTROLLER);

        foreach ($controllerUsers as $user) {
            $redirectLink = '/study/'.$studyName.'/role/'.Constants::ROLE_CONTROLLER.'/visit/'.$visitId;
            $magicLink = $frameworkInterface->createMagicLink($user['id'], $redirectLink);
            $mailServices->sendAutoQC($studyName, $visitType, $patientCode, $studyInfo, $seriesInfo, $magicLink, $user['email'], );
        }
    }
}
