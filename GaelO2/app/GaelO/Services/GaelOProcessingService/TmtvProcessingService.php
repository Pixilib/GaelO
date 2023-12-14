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

class TmtvProcessingService
{

    private VisitRepositoryInterface $visitRepositoryInterface;
    private StudyRepository $studyRepository;
    private DicomStudyRepositoryInterface $dicomStudyRepositoryInterface;
    private DicomSeriesRepositoryInterface $dicomSeriesRepositoryInterface;
    private OrthancService $orthancService;
    private GaelOProcessingService $gaelOProcessingService;
    private string $rawInferenceMaskId;
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

    public function runInference() :void
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
        $this->rawInferenceMaskId = $maskId;
        $this->addCreatedRessource('masks', $maskId);
    }

    public function fragmentInference() :string
    {
        $fragmentedMaskId = $this->gaelOProcessingService->fragmentMask($this->ptOrthancSeriesId, $this->rawInferenceMaskId, true);
        $this->addCreatedRessource('masks', $fragmentedMaskId);
        return $fragmentedMaskId;
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
