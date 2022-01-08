<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Interfaces\Repositories\DicomStudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\DicomSeriesRepositoryInterface;

class DicomSeriesService
{

    private DicomSeriesRepositoryInterface $dicomSeriesRepositoryInterface;
    private DicomStudyRepositoryInterface $dicomStudyRepositoryInterface;
    private VisitService $visitService;

    public function __construct(
        DicomSeriesRepositoryInterface $dicomSeriesRepositoryInterface,
        DicomStudyRepositoryInterface $dicomStudyRepositoryInterface,
        VisitService $visitService
    ) {

        $this->dicomSeriesRepositoryInterface = $dicomSeriesRepositoryInterface;
        $this->dicomStudyRepositoryInterface = $dicomStudyRepositoryInterface;
        $this->visitService = $visitService;
    }

    public function deleteSeries(string $seriesInstanceUID, string $role)
    {

        $seriesData = $this->dicomSeriesRepositoryInterface->getSeries($seriesInstanceUID, false);
        $studyInstanceUID = $seriesData['dicom_study']['study_uid'];
        $visitId = $seriesData['dicom_study']['visit_id'];

        $this->dicomSeriesRepositoryInterface->deleteSeries($seriesInstanceUID);

        $remainingSeries = $this->dicomStudyRepositoryInterface->getChildSeries($studyInstanceUID, false);

        if (sizeof($remainingSeries) === 0) {
            $this->dicomStudyRepositoryInterface->delete($seriesData['dicom_study']['study_uid']);
            $this->visitService->setVisitId($visitId);
            $this->visitService->updateUploadStatus(Constants::UPLOAD_STATUS_NOT_DONE);
            //Reset QC only if suppervisor, we don't change QC status for investigator and controller (as it still ongoing)
            if ($role === Constants::ROLE_SUPERVISOR) {
                $this->visitService->resetQc();
            }

        }
    }

    public function reactivateDicomStudy(string $studyInstanceUID): void
    {

        //Get data from StudyInstanceUID
        $studyData = $this->dicomStudyRepositoryInterface->getDicomStudy($studyInstanceUID, true);

        $visitId = $studyData['visit_id'];

        //Check no other activated study for this visit
        if ($this->dicomStudyRepositoryInterface->isExistingDicomStudyForVisit($visitId) ) {
            throw new GaelOBadRequestException("Already existing Dicom Study for this visit");
        };

        //reactivate study level
        $this->dicomStudyRepositoryInterface->reactivateByStudyInstanceUID($studyInstanceUID);

        //Update upload status to Done
        $this->visitService->setVisitId($visitId);
        $this->visitService->updateUploadStatus(Constants::UPLOAD_STATUS_DONE);
    }
}
