<?php

namespace App\Jobs;
namespace Enum;

use App\GaelO\Constants\Constants;
use App\GaelO\Entities\DicomStudyEntity;
use App\GaelO\Entities\VisitEntity;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Repositories\DicomStudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
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

enum ImageType : string
{
    case MIP = 'MIP';
    case MOSAIC = 'MOSAIC';
    case DEFAULT = 'DEFAULT';
}

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

    public function getImageType(SharedTags $sharedTags): ImageType
    {
        $mosaicIDs = ['1.2.840.10008.5.1.4.1.1.4', '1.2.840.10008.5.1.4.1.1.4.1'];
        $gifIDs = ['1.2.840.10008.5.1.4.1.1.2', '1.2.840.10008.5.1.4.1.1.2.1', '1.2.840.10008.5.1.4.1.1.20',
            '1.2.840.10008.5.1.4.1.1.128', '1.2.840.10008.5.1.4.1.1.130', '1.2.840.10008.5.1.4.1.1.128.1'];

        $SOPClassUID = $sharedTags->getSOPClassUID();
        if (in_array($SOPClassUID, $mosaicIDs)) {
            return ImageType::MOSAIC;
        } elseif (in_array($SOPClassUID, $gifIDs)) {
            return ImageType::MIP;
        } else {
            return ImageType::DEFAULT;
        }
    }

    public function getImagePath(SharedTags $sharedTags, string $seriesID, string $firstInstanceID, OrthancService $orthancService): string
    {
        $imageType = $this->getImageType($sharedTags);
        $imagePath = '';
        switch ($imageType) {
            case ImageType::MIP:
                $imagePath = $orthancService->getSeriesMIP($seriesID);
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

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(FrameworkInterface $frameworkInterface, UserRepositoryInterface $userRepositoryInterface, VisitRepositoryInterface $visitRepositoryInterface, DicomStudyRepositoryInterface $dicomStudyRepositoryInterface, 
        MailServices $mailServices, OrthancService $orthancService, ReviewRepositoryInterface $reviewRepositoryInterface)
    {
        $visitEntity = $visitRepositoryInterface->getVisitContext($this->visitId);
        $dicomStudyEntity = $dicomStudyRepositoryInterface->getDicomsDataFromVisit($this->visitId, false, false);
        $sharedTags = new SharedTags($orthancService->getSharedTags($this->visitId));
    
        $stateInvestigatorForm = $visitEntity['state_investigator_form'];

        $studyInfo = [];
        $studyInfo['studyName'] = $visitEntity['patient']['study_name'];
        $studyInfo['studyDescription'] = $sharedTags->getStudyDescription();
        $studyInfo['studyManufacturer'] = $sharedTags->getStudyManufacturer();
        $studyInfo['studyDate'] = $sharedTags->getStudyDate();
        $studyInfo['studyTime'] = $sharedTags->getStudyTime();
        $studyInfo['numberOfSeries'] = count($dicomStudyEntity[0]['dicom_series']);
        $studyInfo['numberOfInstances'] = 0;
        if ($stateInvestigatorForm != 'Not needed') {
            $studyInfo['investigatorForm'] = json_encode($reviewRepositoryInterface->getInvestigatorForm($this->visitId, false),
                JSON_PRETTY_PRINT);
        } else {
            $studyInfo['investigatorForm'] = null;
        }
        
        $seriesInfo = [];
        foreach ($dicomStudyEntity[0]['dicom_series'] as $series) {
            $studyInfo['numberOfInstances'] += $series['number_of_instances'];
            if ($series['dicom_instances'][0])
                $firstInstanceID = $series['dicom_instances'][0]['instance_id'];

            $seriesData = [];
            $seriesData['infos'] = [];
            $seriesSharedTags = new SharedTags($orthancService->getSharedTags($series['orthanc_id']));
            $seriesData['series_description'] = $seriesSharedTags->getSeriesDescription();
            $seriesData['image_path'] = $this->getImagePath($seriesSharedTags, $series['orthanc_id'], $firstInstanceID, $orthancService);
            $seriesData['infos']['Modality'] = $seriesSharedTags->getSeriesModality();
            $seriesData['infos']['Series date'] =  $seriesSharedTags->getSeriesDate();
            $seriesData['infos']['Series time'] = $series['acquisition_time'];
            $seriesData['infos']['Slice thickness'] = $seriesSharedTags->getSliceThickness();
            $seriesData['infos']['Pixel_spacing'] = $seriesSharedTags->getPixelSpacing();
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
                $seriesData['infos'][] = [$seriesData['infos'], ...$seriesSharedTags->getRadioPharmaceuticalTags($orthancService,
                    $firstInstanceID)];
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
            $mailServices->sendAutoQC($studyName, $visitType, $patientCode, $studyInfo, $seriesInfo, $magicLink, $user['email']);
        }
    }
}
