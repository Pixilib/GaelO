<?php

namespace App\Jobs;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Interfaces\Repositories\DicomStudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Repositories\StudyRepository;
use App\GaelO\Services\GaelOClientService;
use App\GaelO\Services\GaelOProcessingService\GaelOProcessingService;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\OrthancService;
use App\GaelO\Services\PdfServices;
use App\Jobs\RadiomicsReport\GaelOProcessingFile;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use DateTime;
use Exception;
use Throwable;

class JobRadiomicsReport implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $failOnTimeout = true;
    public $timeout = 1200;
    public $tries = 1;
    private int $visitId;
    private ?int $behalfUserId;
    private ?array $destinatorEmails;
    private array $createdFiles = [];
    private GaelOProcessingService $gaelOProcessingService;
    private OrthancService $orthancService;

    public function __construct(int $visitId, ?int $behalfUserId, ?array $destinatorEmails)
    {
        $this->onQueue('processing');
        $this->visitId = $visitId;
        $this->behalfUserId = $behalfUserId;
        $this->destinatorEmails = $destinatorEmails;
    }

    public function handle(
        VisitRepositoryInterface $visitRepositoryInterface,
        StudyRepository $studyRepository,
        DicomStudyRepositoryInterface $dicomStudyRepositoryInterface,
        OrthancService $orthancService,
        GaelOProcessingService $gaelOProcessingService,
        MailServices $mailServices,
        PdfServices $pdfServices,
        GaelOClientService $gaeloClientService
    ): void {

        $this->gaelOProcessingService = $gaelOProcessingService;
        $this->orthancService = $orthancService;
        $this->orthancService->setOrthancServer(true);

        $visitEntity = $visitRepositoryInterface->getVisitContext($this->visitId);
        $studyName = $visitEntity['patient']['study_name'];
        $visitType = $visitEntity['visit_type']['name'];
        $patientCode = $visitEntity['patient']['code'];
        $creatorUserId = $visitEntity['creator_user_id'];
        $existingFiles = $visitEntity['sent_files'];
        $visitDate = new DateTime($visitEntity['visit_date']);
        $formattedVisitDate = $visitDate->format('m/d/Y');
        $dicomStudyEntity = $dicomStudyRepositoryInterface->getDicomsDataFromVisit($this->visitId, false, false);

        $orthancIds = $this->getSeriesOrthancIds($dicomStudyEntity);
        $orthancSeriesIdPt = $orthancIds['orthancSeriesIdPt'];
        $orthancSeriesIdCt = $orthancIds['orthancSeriesIdCt'];

        $this->sendDicomToProcessing($orthancSeriesIdPt);
        $this->sendDicomToProcessing($orthancSeriesIdCt);

        $idPT = $this->gaelOProcessingService->createSeriesFromOrthanc($orthancSeriesIdPt, true, true);
        $this->addCreatedRessource('series', $idPT);
        $idCT = $this->gaelOProcessingService->createSeriesFromOrthanc($orthancSeriesIdCt);
        $this->addCreatedRessource('series', $idCT);

        $inferencePayload = [
            'idPT' => $idPT,
            'idCT' => $idCT
        ];
        $inferenceResponse = $this->gaelOProcessingService->executeInference('unet_model', $inferencePayload);
        $maskId = $inferenceResponse['id_mask'];
        $this->addCreatedRessource('masks', $maskId);

        #Do Mask Fragmentation and threshold
        $fragmentedMaskId = $this->gaelOProcessingService->fragmentMask($idPT, $maskId, true);
        $this->addCreatedRessource('masks', $fragmentedMaskId);
        $threshold41MaskId = $this->gaelOProcessingService->thresholdMask($fragmentedMaskId, $idPT, "41%");
        $this->addCreatedRessource('masks', $threshold41MaskId);

        #Fragmented Mip
        $mipFragmentedPayload = ['maskId' => $threshold41MaskId, 'delay' => 0.3, 'min' => 0, 'max' => 5, 'inverted' => true, 'orientation' => 'LPI'];
        $mipMask = $this->gaelOProcessingService->createMIPForSeries($idPT, $mipFragmentedPayload);

        #Download Rtss
        #$rtssId = $this->gaelOProcessingService->createRtssFromMask($orthancSeriesIdPt, $threshold41MaskId);
        #$this->addCreatedRessource('rtss', $rtssId);
        #$rtssFile = $this->gaelOProcessingService->getRtss($rtssId);

        #Download Seg
        #$segId = $this->gaelOProcessingService->createSegFromMask($orthancSeriesIdPt, $threshold41MaskId);
        #$this->addCreatedRessource('seg', $segId);
        #$segFile = $this->gaelOProcessingService->getSeg($segId);

        #Download .nii.gz Mask Dicom (not thrsholded)
        $maskdicom = $this->gaelOProcessingService->getMaskDicomOrientation($fragmentedMaskId, 'LPI', true);

        #get Stats
        $stats = $this->gaelOProcessingService->getStatsMaskSeries($threshold41MaskId, $idPT);
        $statValue = [
            'TMTV 41%' => $stats['volume'],
            'Dmax (voxel)' => $stats['dmax'],
            'SUVmax' => $stats['suvmax'],
            'SUVmean'=> $stats['suvmean'],
            'SUVpeak' => $stats['suvpeak'],
            'TLG' => $stats['tlg'],
            'Dmax Bulk' => $stats['dmaxbulk'],
        ];

        if($this->destinatorEmails){
            $mailServices->sendRadiomicsReport(
                $studyName,
                $patientCode,
                $visitType,
                $formattedVisitDate,
                $mipMask,
                $statValue,
                $this->destinatorEmails
            );
        }

        $pdfReport  = $pdfServices->saveRadiomicsPdf(
            $studyName,
            $patientCode,
            $visitType,
            $formattedVisitDate,
            $statValue
        );

        //Send file to store using API as job worker may not access to the storage backend
        if ($this->behalfUserId) {
            $user = User::find($this->behalfUserId);
        } else {
            $studyEntity = $studyRepository->find($studyName);
            $user = User::where('email', $studyEntity->contactEmail)->sole();
        }

        $tokenResult = $user->createToken('GaelO')->plainTextToken;
        $gaeloClientService->loadUrl();
        $gaeloClientService->setAuthorizationToken($tokenResult);
        //In case of changed upload remove the last mask
        if (array_key_exists('tmtv41', $existingFiles)) {
            $gaeloClientService->deleteFileToVisit($studyName, $this->visitId, 'tmtv41');
        }
        if (array_key_exists('tmtvReport', $existingFiles)) {
            $gaeloClientService->deleteFileToVisit($studyName, $this->visitId, 'tmtvReport');
        }
        if (array_key_exists('mipSegmentation', $existingFiles)) {
            $gaeloClientService->deleteFileToVisit($studyName, $this->visitId, 'mipSegmentation');
        }
        //Store the file for review availability
        $gaeloClientService->createFileToVisit($studyName, $this->visitId, 'tmtv41', 'nii.gz', $maskdicom);
        $gaeloClientService->createFileToVisit($studyName, $this->visitId, 'tmtvReport', 'pdf', $pdfReport);
        $gaeloClientService->createFileToVisit($studyName, $this->visitId, 'mipSegmentation', 'gif', $mipMask);

        $this->deleteCreatedRessources();
    }

    private function sendDicomToProcessing(string $orthancSeriesIdPt)
    {
        $temporaryZipDicom  = tempnam(ini_get('upload_tmp_dir'), 'TMP_Inference_');
        $this->orthancService->getZipStreamToFile([$orthancSeriesIdPt], $temporaryZipDicom);
        $this->gaelOProcessingService->createDicom($temporaryZipDicom);
        $this->addCreatedRessource('dicoms', $orthancSeriesIdPt);

        unlink($temporaryZipDicom);
    }

    private function getSeriesOrthancIds(array $dicomStudyEntity)
    {
        $idPT = null;
        $idCT = null;
        foreach ($dicomStudyEntity[0]['dicom_series'] as $series) {
            if ($series['modality'] == 'PT') {
                if ($idPT) throw new GaelOException('Multiple PET Series, unable to perform segmentation');
                $idPT = $series['orthanc_id'];
            }
            if ($series['modality'] == 'CT') {
                if ($idCT) throw new GaelOException('Multiple CT Series, unable to perform segmentation');
                $idCT = $series['orthanc_id'];
            }
        }

        if (!$idPT || !$idCT) {
            //Can happen in case of a study reactivation, at reactivation series are softdeleted so we won't run the inference
            throw new GaelOException("Didn't found CT and PT Series to run the inference");
        }

        return [
            'orthancSeriesIdPt' => $idPT,
            'orthancSeriesIdCt' => $idCT
        ];
    }

    private function addCreatedRessource(string $type, string $id)
    {
        $this->createdFiles[] = new GaelOProcessingFile($type, $id);
    }

    private function deleteCreatedRessources()
    {
        foreach ($this->createdFiles as $gaeloProcessingFile) {
            try {
                $this->gaelOProcessingService->deleteRessource($gaeloProcessingFile->getType(), $gaeloProcessingFile->getId());
            } catch (Exception) {
            }
        }
    }

    public function failed(Throwable $exception)
    {
        $mailServices = App::make(MailServices::class);
        $mailServices->sendJobFailure('RadiomicsReport', ['visitId' => $this->visitId, 'behalfUserId' => $this->behalfUserId], $exception->getMessage());
        if (app()->bound('sentry')) {
            app('sentry')->captureException($exception);
        }
    }
}
