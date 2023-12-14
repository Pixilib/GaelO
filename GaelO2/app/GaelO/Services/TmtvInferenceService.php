<?php

namespace App\GaelO\Services;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Interfaces\Repositories\DicomSeriesRepositoryInterface;
use App\GaelO\Interfaces\Repositories\DicomStudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Repositories\StudyRepository;
use App\GaelO\Services\GaelOProcessingService\GaelOProcessingService;
use App\Jobs\RadiomicsReport\GaelOProcessingFile;
use Exception;

class TmtvInferenceService
{

    private VisitRepositoryInterface $visitRepositoryInterface;
    private StudyRepository $studyRepository;
    private DicomStudyRepositoryInterface $dicomStudyRepositoryInterface;
    private DicomSeriesRepositoryInterface $dicomSeriesRepositoryInterface;
    private OrthancService $orthancService;
    private GaelOProcessingService $gaelOProcessingService;
    private string $ptOrthancSeriesId;
    private string $ctOrthancSeriesId;
    private array $createdFiles = [];


    public function __construct(
        VisitRepositoryInterface $visitRepositoryInterface,
        StudyRepository $studyRepository,
        DicomStudyRepositoryInterface $dicomStudyRepositoryInterface,
        DicomSeriesRepositoryInterface $dicomSeriesRepositoryInterface,
        OrthancService $orthancService,
        GaelOProcessingService $gaelOProcessingService,

    ) {
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->studyRepository = $studyRepository;
        $this->dicomStudyRepositoryInterface = $dicomStudyRepositoryInterface;
        $this->dicomSeriesRepositoryInterface = $dicomSeriesRepositoryInterface;
        $this->gaelOProcessingService = $gaelOProcessingService;
        $this->orthancService = $orthancService;
        $this->orthancService->setOrthancServer(true);
    }

    public function runInference()
    {

        $this->sendDicomToProcessing($this->ptOrthancSeriesId);
        $this->sendDicomToProcessing($this->ctOrthancSeriesId);

        $idPT = $this->gaelOProcessingService->createSeriesFromOrthanc($this->ptOrthancSeriesId, true, true);
        $this->addCreatedRessource('series', $idPT);
        $idCT = $this->gaelOProcessingService->createSeriesFromOrthanc($this->ctOrthancSeriesId);
        $this->addCreatedRessource('series', $idCT);

        $inferencePayload = [
            'idPT' => $idPT,
            'idCT' => $idCT
        ];

        $inferenceResponse = $this->gaelOProcessingService->executeInference('unet_model', $inferencePayload);
        $maskId = $inferenceResponse['id_mask'];
        $this->addCreatedRessource('masks', $maskId);
    }

    public function fragmentInference()
    {
        $fragmentedMaskId = $this->gaelOProcessingService->fragmentMask($idPT, $maskId, true);
        $this->addCreatedRessource('masks', $fragmentedMaskId);
    }

    public function thresholdMask()
    {
        $threshold41MaskId = $this->gaelOProcessingService->thresholdMask($fragmentedMaskId, $idPT, "41%");
        $this->addCreatedRessource('masks', $threshold41MaskId);
    }

    public function createTepMaskMip()
    {
        $mipFragmentedPayload = ['maskId' => $threshold41MaskId, 'delay' => 0.3, 'min' => 0, 'max' => 5, 'inverted' => true, 'orientation' => 'LPI'];
        $mipMask = $this->gaelOProcessingService->createMIPForSeries($idPT, $mipFragmentedPayload);
    }

    public function getMaskAs(string $type, string $orientation = null)
    {
        if ($type === 'nifti') {
            $maskdicom = $this->gaelOProcessingService->getMaskDicomOrientation($fragmentedMaskId, 'LPI', true);
        } else if ($type === "seg") {
            $rtssId = $this->gaelOProcessingService->createRtssFromMask($orthancSeriesIdPt, $threshold41MaskId);
            $this->addCreatedRessource('rtss', $rtssId);
            $rtssFile = $this->gaelOProcessingService->getRtss($rtssId);
        } else if ($type === "rtss") {
            $segId = $this->gaelOProcessingService->createSegFromMask($orthancSeriesIdPt, $threshold41MaskId);
            $this->addCreatedRessource('seg', $segId);
            $segFile = $this->gaelOProcessingService->getSeg($segId);
        }
    }

    public function getStatsOfMask()
    {
        $stats = $this->gaelOProcessingService->getStatsMaskSeries($threshold41MaskId, $idPT);
        $statValue = [
            'TMTV 41%' => $stats['volume'],
            'Dmax (voxel)' => $stats['dmax'],
            'SUVmax' => $stats['suvmax'],
            'SUVmean' => $stats['suvmean'],
            'SUVpeak' => $stats['suvpeak'],
            'TLG' => $stats['tlg'],
            'Dmax Bulk' => $stats['dmaxbulk'],
        ];
    }

    protected function sendDicomToProcessing(string $orthancSeriesIdPt)
    {
        $temporaryZipDicom  = tempnam(ini_get('upload_tmp_dir'), 'TMP_Inference_');
        $this->orthancService->getZipStreamToFile([$orthancSeriesIdPt], $temporaryZipDicom);
        $this->gaelOProcessingService->createDicom($temporaryZipDicom);
        $this->addCreatedRessource('dicoms', $orthancSeriesIdPt);

        unlink($temporaryZipDicom);
    }

    protected function extractPetAndCtSeriesOrthancIds(array $dicomStudyEntities): void
    {
        $idPT = null;
        $idCT = null;
        foreach ($dicomStudyEntities[0]['dicom_series'] as $series) {
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

        $this->ctOrthancSeriesId = $idCT;
        $this->ptOrthancSeriesId = $idPT;
    }

    protected function addCreatedRessource(string $type, string $id)
    {
        $this->createdFiles[] = new GaelOProcessingFile($type, $id);
    }

    protected function deleteCreatedRessources()
    {
        foreach ($this->createdFiles as $gaeloProcessingFile) {
            try {
                $this->gaelOProcessingService->deleteRessource($gaeloProcessingFile->getType(), $gaeloProcessingFile->getId());
            } catch (Exception) {
            }
        }
    }
}
