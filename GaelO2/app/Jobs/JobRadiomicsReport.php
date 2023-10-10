<?php

namespace App\Jobs;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Repositories\DicomStudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\GaelOClientService;
use App\GaelO\Services\GaelOProcessingService\GaelOProcessingService;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\OrthancService;
use App\GaelO\Services\PdfServices;
use App\Jobs\RadiomicsReport\GaelOProcessingFile;
use App\Models\User;
use DateTime;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Throwable;

class JobRadiomicsReport implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $failOnTimeout = true;
    public $timeout = 1200;
    public $tries = 1;
    private int $visitId;
    private string $behalfUserEmail;
    private array $createdFiles = [];
    private GaelOProcessingService $gaelOProcessingService;

    public function __construct(int $visitId, string $behalfUserEmail)
    {
        $this->onQueue('processing');
        $this->visitId = $visitId;
        $this->behalfUserEmail = $behalfUserEmail;
    }

    public function handle(VisitRepositoryInterface $visitRepositoryInterface, DicomStudyRepositoryInterface $dicomStudyRepositoryInterface, OrthancService $orthancService, GaelOProcessingService $gaelOProcessingService, FrameworkInterface $frameworkInterface, MailServices $mailServices, PdfServices $pdfServices, GaelOClientService $gaeloClientService): void
    {
        $this->gaelOProcessingService = $gaelOProcessingService;
        $orthancService->setOrthancServer(true);
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

        $downloadedFilePathPT  = tempnam(ini_get('upload_tmp_dir'), 'TMP_Inference_');
        $orthancService->getZipStreamToFile([$orthancSeriesIdPt], fopen($downloadedFilePathPT, 'r+'));

        $downloadedFilePathCT  = tempnam(ini_get('upload_tmp_dir'), 'TMP_Inference_');
        $orthancService->getZipStreamToFile([$orthancSeriesIdCt], fopen($downloadedFilePathCT, 'r+'));

        $gaelOProcessingService->createDicom($downloadedFilePathPT);
        $this->addCreatedRessource('dicoms', $orthancSeriesIdPt);
        $gaelOProcessingService->createDicom($downloadedFilePathCT);
        $this->addCreatedRessource('dicoms', $orthancSeriesIdCt);

        $idPT =  $gaelOProcessingService->createSeriesFromOrthanc($orthancSeriesIdPt, true, true);
        $this->addCreatedRessource('series', $idPT);
        $idCT =  $gaelOProcessingService->createSeriesFromOrthanc($orthancSeriesIdCt);
        $this->addCreatedRessource('series', $idCT);

        $inferencePayload = [
            'idPT' => $idPT,
            'idCT' => $idCT
        ];
        $inferenceResponse = $gaelOProcessingService->executeInference('unet_model', $inferencePayload);
        $maskId = $inferenceResponse['id_mask'];
        $this->addCreatedRessource('masks', $maskId);

        #Do Mask Fragmentation and threshold
        $fragmentedMaskId = $gaelOProcessingService->fragmentMask($idPT, $maskId, true);
        $this->addCreatedRessource('masks', $fragmentedMaskId);
        $threshold41MaskId = $gaelOProcessingService->thresholdMask($fragmentedMaskId, $idPT, "41%");
        $this->addCreatedRessource('masks', $threshold41MaskId);

        #Fragmented Mip
        $mipFragmentedPayload = ['maskId' => $threshold41MaskId, 'delay' => 0.3, 'min' => 0, 'max' => 5, 'inverted' => true, 'orientation' => 'LPI'];
        $mipMask = $gaelOProcessingService->createMIPForSeries($idPT, $mipFragmentedPayload);

        #get Rtss
        #$rtssId = $gaelOProcessingService->createRtssFromMask($orthancSeriesIdPt, $threshold41MaskId);
        #$this->addCreatedRessource('rtss', $rtssId);
        #$rtssFile = $gaelOProcessingService->getRtss($rtssId);

        #get Seg
        #$segId = $gaelOProcessingService->createSegFromMask($orthancSeriesIdPt, $threshold41MaskId);
        #$this->addCreatedRessource('seg', $segId);
        #$segFile = $gaelOProcessingService->getSeg($segId);

        #get .nii.gz Mask Dicom (not thrsholded)
        $maskdicom = $gaelOProcessingService->getMaskDicomOrientation($fragmentedMaskId, 'LPI', true);

        #get Stats
        $stats = $gaelOProcessingService->getStatsMask($threshold41MaskId);
        $statValue = ['tmtv 41%' => $stats['volume'], 'dmax' => $stats['dMax']];
        $mailServices->sendRadiomicsReport(
            $studyName,
            $patientCode,
            $visitType,
            $formattedVisitDate,
            $mipMask,
            $statValue,
            $creatorUserId
        );
        $pdfReport  = $pdfServices->saveRadiomicsPdf(
            $studyName,
            $patientCode,
            $visitType,
            $formattedVisitDate,
            $statValue
        );

        //Send file to store using API as job worker may not access to the storage backend
        $user = User::where('email', $this->behalfUserEmail)->sole();
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

    public function sendFailureEmail(Throwable $exception)
    {
        $mailServices = App::make(MailServices::class);
        $mailServices->sendJobFailure('RadiomicsReport', ['visitId' => $this->visitId, 'behalfUser' => $this->behalfUserEmail], $exception->getMessage());
    }

    public function failed(Throwable $exception)
    {
        Log::error($exception);
        $this->sendFailureEmail($exception);
    }
}
