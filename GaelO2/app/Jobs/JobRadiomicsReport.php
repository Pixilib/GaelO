<?php

namespace App\Jobs;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Repositories\DicomStudyRepositoryInterface;
use App\GaelO\Services\GaelOProcessingService\GaelOProcessingService;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\OrthancService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class JobRadiomicsReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $failOnTimeout = true;
    public $timeout = 300;
    public $tries = 1;
    private int $visitId;

    public function __construct(int $visitId)
    {
        $this->visitId = $visitId;
    }

    public function handle(DicomStudyRepositoryInterface $dicomStudyRepositoryInterface, OrthancService $orthancService, GaelOProcessingService $gaelOProcessingService, FrameworkInterface $frameworkInterface, MailServices $mailServices): void
    {
        $orthancService->setOrthancServer(true);
        $dicomStudyEntity = $dicomStudyRepositoryInterface->getDicomsDataFromVisit($this->visitId, false, false);

        $orthancIds = $this->getSeriesOrthancIds($dicomStudyEntity);
        $orthancSeriesIdPt = $orthancIds['orthancSeriesIdPt'];
        $orthancSeriesIdCt = $orthancIds['orthancSeriesIdCt'];

        $downloadedFilePathPT  = tempnam(ini_get('upload_tmp_dir'), 'TMP_Inference_');
        $orthancService->getZipStreamToFile([$orthancSeriesIdPt], fopen($downloadedFilePathPT, 'r+'));

        $downloadedFilePathCT  = tempnam(ini_get('upload_tmp_dir'), 'TMP_Inference_');
        $orthancService->getZipStreamToFile([$orthancSeriesIdCt], fopen($downloadedFilePathCT, 'r+'));

        $gaelOProcessingService->createDicom($downloadedFilePathPT);
        $gaelOProcessingService->createDicom($downloadedFilePathCT);

        $idPT =  $gaelOProcessingService->createSeriesFromOrthanc($orthancSeriesIdPt);
        $idCT =  $gaelOProcessingService->createSeriesFromOrthanc($orthancSeriesIdCt);

        $inferencePayload = [
            'idPT' => $idPT,
            'idCT' => $idCT
        ];
        $inferenceResponse = $gaelOProcessingService->executeInference('attentionunet_model', $inferencePayload);
        $maskId = $inferenceResponse['id_mask'];

        #Do Mask Fragmentation and threshold
        $fragmentedMaskId = $gaelOProcessingService->fragmentMask($idPT, $maskId);
        $threshold41MaskId = $gaelOProcessingService->thresholdMask($fragmentedMaskId, $idPT, "41%");

        #Fragmented Mip
        $mipFragmentedPayload = ['maskId' => $threshold41MaskId, 'min' => 0, 'max' => 5, 'inverted' => true, 'orientation' => 'LPI'];
        $mipMask = $gaelOProcessingService->createMIPForSeries($idPT, $mipFragmentedPayload);
        $frameworkInterface->storeFile('fragmentedInferenceTest.gif', fopen($mipMask, 'r'));

        #get Rtss
        $rtssId = $gaelOProcessingService->createRtssFromMask($orthancSeriesIdPt, $threshold41MaskId);
        $rtssFile = $gaelOProcessingService->getRtss($rtssId);
        $frameworkInterface->storeFile('rtss_41.dcm', fopen($rtssFile, 'r'));

        #get Seg
        $segId = $gaelOProcessingService->createSegFromMask($orthancSeriesIdPt, $threshold41MaskId);
        $segFile = $gaelOProcessingService->getSeg($segId);
        $frameworkInterface->storeFile('seg_41.dcm', fopen($segFile, 'r'));

        #get Nifti Dicom
        $maskdicom = $gaelOProcessingService->getMaskDicomOrientation($threshold41MaskId, 'LPI', false);
        $frameworkInterface->storeFile('mask_41_dicom.nii', fopen($maskdicom, 'r'));

        #get Stats
        $stats = $gaelOProcessingService->getStatsMask($threshold41MaskId);
        $mailServices->sendRadiomicsReport("TEST", $mipMask, [
            'tmtv' => $stats['volume'],
            'dmax' => $stats['dMax']
        ]);

        $gaelOProcessingService->deleteRessource('dicoms', $orthancSeriesIdPt);
        $gaelOProcessingService->deleteRessource('dicoms', $orthancSeriesIdCt);
        $gaelOProcessingService->deleteRessource('series', $idPT);
        $gaelOProcessingService->deleteRessource('series', $idCT);
        $gaelOProcessingService->deleteRessource('masks', $maskId);
        $gaelOProcessingService->deleteRessource('masks', $fragmentedMaskId);
        $gaelOProcessingService->deleteRessource('masks', $threshold41MaskId);
        $gaelOProcessingService->deleteRessource('rtss', $rtssId);
        $gaelOProcessingService->deleteRessource('seg', $segId);
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
}
