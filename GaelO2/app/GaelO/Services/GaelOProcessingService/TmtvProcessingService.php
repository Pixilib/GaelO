<?php

namespace App\GaelO\Services\GaelOProcessingService;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Interfaces\Repositories\DicomStudyRepositoryInterface;
use App\GaelO\Services\GaelOProcessingService\GaelOProcessingService;
use App\GaelO\Services\OrthancService;
use App\Jobs\RadiomicsReport\GaelOProcessingFile;
use Exception;

class TmtvProcessingService
{
    protected string $modelName = 'pt_seg_attentionunet_fdg';
    protected DicomStudyRepositoryInterface $dicomStudyRepositoryInterface;
    protected OrthancService $orthancService;
    protected GaelOProcessingService $gaelOProcessingService;
    protected string $ptOrthancSeriesId;
    protected string $ctOrthancSeriesId;
    protected string $ptSeriesUid;
    protected string $ctSeriesUid;
    protected ?string $version = null;
    protected array $createdFiles = [];


    public function __construct(
        DicomStudyRepositoryInterface $dicomStudyRepositoryInterface,
        OrthancService $orthancService,
        GaelOProcessingService $gaelOProcessingService
    ) {
        $this->dicomStudyRepositoryInterface = $dicomStudyRepositoryInterface;
        $this->gaelOProcessingService = $gaelOProcessingService;
        $this->orthancService = $orthancService;
        $this->orthancService->setOrthancServer(true);
    }

    public function setVersion(string $version){
        $this->version = $version;
    }

    public function runInference(): MaskProcessingService
    {

        $this->orthancService->sendDicomToProcessing($this->ptOrthancSeriesId, $this->gaelOProcessingService);
        $this->addCreatedRessource('dicoms', $this->ptOrthancSeriesId);
        $this->orthancService->sendDicomToProcessing($this->ctOrthancSeriesId, $this->gaelOProcessingService);
        $this->addCreatedRessource('dicoms', $this->ctOrthancSeriesId);

        $idPT = $this->gaelOProcessingService->createSeriesFromOrthanc($this->ptOrthancSeriesId, true, true);
        $this->addCreatedRessource('series', $idPT);
        $idCT = $this->gaelOProcessingService->createSeriesFromOrthanc($this->ctOrthancSeriesId);
        $this->addCreatedRessource('series', $idCT);

        $inferencePayload = [
            'idPT' => $idPT,
            'idCT' => $idCT
        ];

        if ($this->version) $inferencePayload['version'] = $this->version;

        $inferenceResponse = $this->gaelOProcessingService->executeInference($this->modelName, $inferencePayload);
        $maskId = $inferenceResponse['id_mask'];
        $maskProcessingService = new MaskProcessingService($this->orthancService, $this->gaelOProcessingService);
        $maskProcessingService->setMaskId($maskId);
        $maskProcessingService->setPetId($idPT, $this->ptOrthancSeriesId);
        $this->addCreatedRessource('masks', $maskId);
        return $maskProcessingService;
    }


    public function loadPetAndCtSeriesOrthancIdsFromVisit($visitId): void
    {
        $dicomStudyEntity = $this->dicomStudyRepositoryInterface->getDicomsDataFromVisit($visitId, false, false);

        $idPT = null;
        $ptSeriesUid = null;
        $idCT = null;
        $ctSeriesUid = null;
        foreach ($dicomStudyEntity[0]['dicom_series'] as $series) {
            if ($series['modality'] == 'PT') {
                if ($idPT) throw new GaelOException('Multiple PET Series, unable to perform segmentation');
                $idPT = $series['orthanc_id'];
                $ptSeriesUid = $series['series_uid'];
            }
            if ($series['modality'] == 'CT') {
                if ($idCT) throw new GaelOException('Multiple CT Series, unable to perform segmentation');
                $idCT = $series['orthanc_id'];
                $ctSeriesUid = $series['series_uid'];
            }
        }

        if (!$idPT || !$idCT) {
            //Can happen in case of a study reactivation, at reactivation series are softdeleted so we won't run the inference
            throw new GaelOException("Didn't found CT and PT Series to run the inference");
        }

        $this->ctOrthancSeriesId = $idCT;
        $this->ptOrthancSeriesId = $idPT;
        $this->ptSeriesUid = $ptSeriesUid;
        $this->ctSeriesUid = $ctSeriesUid;
    }

    public function getInferedPtSeriesUid(): string
    {
        return $this->ptSeriesUid;
    }

    public function addCreatedRessource(string $type, string $id)
    {
        $this->createdFiles[] = new GaelOProcessingFile($type, $id);
    }

    public function deleteCreatedRessources()
    {
        foreach ($this->createdFiles as $gaeloProcessingFile) {
            try {
                $this->gaelOProcessingService->deleteRessource($gaeloProcessingFile->getType(), $gaeloProcessingFile->getId());
            } catch (Exception) {
            }
        }
    }
}
