<?php

namespace App\Jobs;

use App\GaelO\Constants\Constants;
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
use App\GaelO\Services\StoreObjects\OrthancMetaData;
use App\Jobs\ImageType;
use Illuminate\Support\Facades\Log;
use Throwable;

class JobAutoQc implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private int $visitId;

    public $timeout = 120;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $visitId)
    {
        $this->visitId = $visitId;
    }

    public function getImageType(OrthancMetaData $sharedTags): ImageType
    {
        $mosaicIDs = ['1.2.840.10008.5.1.4.1.1.4', '1.2.840.10008.5.1.4.1.1.4.1'];
        $gifIDs = [
            '1.2.840.10008.5.1.4.1.1.2', '1.2.840.10008.5.1.4.1.1.2.1', '1.2.840.10008.5.1.4.1.1.20',
            '1.2.840.10008.5.1.4.1.1.128', '1.2.840.10008.5.1.4.1.1.130', '1.2.840.10008.5.1.4.1.1.128.1'
        ];

        $SOPClassUID = $sharedTags->getSOPClassUID();
        if (in_array($SOPClassUID, $mosaicIDs)) {
            return ImageType::MOSAIC;
        } elseif (in_array($SOPClassUID, $gifIDs)) {
            return ImageType::MIP;
        } else {
            return ImageType::DEFAULT;
        }
    }

    private function getSeriesPreview(OrthancMetaData $sharedTags, string $seriesID, string $firstInstanceID, OrthancService $orthancService): string
    {
        $imageType = $this->getImageType($sharedTags);
        $imagePath = '';
        switch ($imageType) {
            case ImageType::MIP:
                //SK Mosaic temporairement
                $imagePath = $orthancService->getSeriesMosaic($seriesID);
                //$imagePath = $orthancService->getSeriesMIP($seriesID);
                break;
            case ImageType::MOSAIC:
                $imagePath = $orthancService->getSeriesMosaic($seriesID);
                break;
            case ImageType::DEFAULT:
                $imagePath = $orthancService->getInstancePreview($firstInstanceID);
                break;
        }
        return $imagePath;
    }

    private function getRadioPharmaceutical(array $radioPharmaceuticalTags): ?array
    {
        $radioPharmaceuticalArray = [];

        if ($radioPharmaceuticalTags != null) {
            for ($j = 0; $j < count($radioPharmaceuticalTags); $j++) {
                $radioPharmaceuticalArray[$radioPharmaceuticalTags[$j]['Name']] = $radioPharmaceuticalTags[$j]['Value'];
            }
        }
        return $radioPharmaceuticalArray;
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
        $orthancService->setOrthancServer(true);
        $visitEntity = $visitRepositoryInterface->getVisitContext($this->visitId);
        $dicomStudyEntity = $dicomStudyRepositoryInterface->getDicomsDataFromVisit($this->visitId, false, false);

        $stateInvestigatorForm = $visitEntity['state_investigator_form'];

        $studyInfo = [];
        $studyInfo['visitDate'] = $visitEntity['visit_date'];
        $studyInfo['registrationDate'] = $visitEntity['patient']['registration_date'];
        $studyInfo['studyName'] = $visitEntity['patient']['study_name'];

        $studyInfo['numberOfSeries'] = count($dicomStudyEntity[0]['dicom_series']);
        $studyInfo['numberOfInstances'] = 0;
        if ($stateInvestigatorForm != Constants::INVESTIGATOR_FORM_NOT_NEEDED) {
            $studyInfo['investigatorForm'] = json_encode(
                $reviewRepositoryInterface->getInvestigatorForm($this->visitId, false),
                JSON_PRETTY_PRINT
            );
        } else {
            $studyInfo['investigatorForm'] = null;
        }

        $seriesInfo = [];
        $index = 0;
        foreach ($dicomStudyEntity[0]['dicom_series'] as $series) {
            $seriesSharedTags = $orthancService->getMetaData($series['orthanc_id']);
            $seriesDetails = $orthancService->getOrthancRessourcesDetails(Constants::ORTHANC_SERIES_LEVEL, $series['orthanc_id']);

            if ($index == 0) {
                $studyInfo['studyDescription'] = $seriesSharedTags->getStudyDescription();
                $studyInfo['studyManufacturer'] = $seriesSharedTags->getStudyManufacturer();
                $studyInfo['studyDate'] = $seriesSharedTags->getStudyDate();
                $studyInfo['studyTime'] = $seriesSharedTags->getStudyTime();
            }
            $studyInfo['numberOfInstances'] += $series['number_of_instances'];
            $seriesData = [];
            $seriesData['infos'] = [];
            $seriesData['series_description'] = $seriesSharedTags->getSeriesDescription();
            try {
                $seriesData['image_path'] = $this->getSeriesPreview($seriesSharedTags, $series['orthanc_id'], $seriesDetails['Instances'][0], $orthancService);
            } catch (Throwable $t) {
                Log::info($t);
            }
            $seriesData['infos']['Modality'] = $seriesSharedTags->getSeriesModality();
            $seriesData['infos']['Series date'] =  $seriesSharedTags->getSeriesDate();
            $seriesData['infos']['Series time'] = $series['acquisition_time'];
            $seriesData['infos']['Slice thickness'] = $seriesSharedTags->getSliceThickness();
            $seriesData['infos']['Pixel spacing'] = $seriesSharedTags->getPixelSpacing();
            $seriesData['infos']['FOV'] = $seriesSharedTags->getFieldOfView();
            $seriesData['infos']['Matrix size'] = $seriesSharedTags->getMatrixSize();
            $seriesData['infos']['Patient position'] = $seriesSharedTags->getPatientPosition();
            $seriesData['infos']['Patient orientation'] = $seriesSharedTags->getImageOrientation();
            $seriesData['infos']['Number of instances'] = $series['number_of_instances'];
            if ($seriesData['infos']['Modality'] == 'MR') {
                $seriesData['infos']['Scanning sequence'] = $seriesSharedTags->getScanningSequence();
                $seriesData['infos']['Sequence variant'] = $seriesSharedTags->getSequenceVariant();
                $seriesData['infos']['Echo_ ime'] = $seriesSharedTags->getEchoTime();
                $seriesData['infos']['Inversion_time'] = $seriesSharedTags->getInversionTime();
                $seriesData['infos']['Echo train length'] = $seriesSharedTags->getEchoTrainLength();
                $seriesData['infos']['Spacing between slices'] = $seriesSharedTags->getSpacingBetweenSlices();
                $seriesData['infos']['Protocol name'] = $seriesSharedTags->getProtocolName();
            } else if ($seriesData['infos']['Modality'] == 'PT') {
                $seriesData['infos']['Patient weight'] = $seriesSharedTags->getPatientWeight();
                $seriesData['infos']['Patient height'] = $seriesSharedTags->getPatientHeight();
                //Add radiopharmaceutical data (need first instance metadata to access it)
                $instanceTags = $orthancService->getInstanceTags($seriesDetails['Instances'][0]);
                $seriesData['infos']['Injected Dose'] = $instanceTags->getInjectedDose();
                $seriesData['infos']['Injected Time'] = $instanceTags->getInjectedTime();
                $seriesData['infos']['Injected DateTime'] = $instanceTags->getInjectedDateTime();
                $seriesData['infos']['Injected Activity'] = $instanceTags->getInjectedActivity();
                $seriesData['infos']['Radiopharmaceutical'] = $instanceTags->getRadiopharmaceutical();
                $seriesData['infos']['Half Life'] = $instanceTags->getHalfLife();
            }
            $seriesInfo[] = $seriesData;
        }

        $studyName = $visitEntity['patient']['study_name'];
        $visitId = $visitEntity['id'];
        $visitType = $visitEntity['visit_type']['name'];
        $patientCode = $visitEntity['patient']['code'];

        $controllerUsers = $userRepositoryInterface->getUsersByRolesInStudy($studyName, Constants::ROLE_CONTROLLER);

        foreach ($controllerUsers as $user) {
            $redirectLink = '/study/' . $studyName . '/role/' . Constants::ROLE_CONTROLLER . '/visit/' . $visitId;
            $magicLink = $frameworkInterface->createMagicLink($user['id'], $redirectLink);
            $mailServices->sendAutoQC($studyName, $visitType, $patientCode, $studyInfo, $seriesInfo, $magicLink, $user['email']);
        }
    }
}
