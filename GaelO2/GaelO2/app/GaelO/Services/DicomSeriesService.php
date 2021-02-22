<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Interfaces\ReviewRepositoryInterface;
use App\GaelO\Repositories\OrthancSeriesRepository;
use App\GaelO\Repositories\OrthancStudyRepository;

class DicomSeriesService
{

    private OrthancSeriesRepository $orthancSeriesRepository;
    private OrthancStudyRepository $orthancStudyRepository;
    private VisitService $visitService;

    public function __construct(
        OrthancSeriesRepository $orthancSeriesRepository,
        OrthancStudyRepository $orthancStudyRepository,
        ReviewRepositoryInterface $reviewRepositoryInterface,
        VisitService $visitService
    ) {

        $this->orthancSeriesRepository = $orthancSeriesRepository;
        $this->orthancStudyRepository = $orthancStudyRepository;
        $this->visitService = $visitService;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
    }

    public function deleteSeries(string $seriesInstanceUID, string $role)
    {

        $seriesData = $this->orthancSeriesRepository->getSeriesBySeriesInstanceUID($seriesInstanceUID, false);
        $studyOrthancID = $seriesData['orthanc_study']['orthanc_id'];
        $visitId = $seriesData['orthanc_study']['visit_id'];

        $this->orthancSeriesRepository->deletebySeriesInstanceUID($seriesInstanceUID);

        $remainingSeries = $this->orthancStudyRepository->getChildSeries($studyOrthancID, false);

        if (sizeof($remainingSeries) === 0) {
            $this->orthancStudyRepository->delete($seriesData['orthanc_study']['orthanc_id']);
            $this->visitService->setVisitId($visitId);
            $this->visitService->updateUploadStatus(Constants::UPLOAD_STATUS_NOT_DONE);
            //Reset QC only if suppervisor, we don't change QC status for investigator and controller (as it still ongoing)
            if ($role === Constants::ROLE_SUPERVISOR) {
                $this->visitService->resetQc();
            }

        }
    }



    public function getSeriesBySeriesInstanceUID(string $seriesInstanceUID, bool $includeDeleted)
    {
        return $this->orthancSeriesRepository->getSeriesBySeriesInstanceUID($seriesInstanceUID, $includeDeleted);
    }

    public function getStudyByStudyInstanceUID(string $studyInstanceUID, bool $includeDeleted): array
    {
        return $this->orthancStudyRepository->getOrthancStudyByStudyInstanceUID($studyInstanceUID, $includeDeleted);
    }

    public function reactivateOrthancStudyByStudyInstanceUID(string $studyInstanceUID): void
    {

        //Get data from StudyInstanceUID
        $studyData = $this->orthancStudyRepository->getOrthancStudyByStudyInstanceUID($studyInstanceUID, true);

        //Check no other activated study for this visit
        if ($this->orthancStudyRepository->isExistingDicomStudyForVisit($studyData['visit_id'])) {
            throw new GaelOBadRequestException("Already existing Dicom Study for this visit");
        };

        //reactivate study level
        $this->orthancStudyRepository->reactivateByStudyInstanceUID($studyInstanceUID);
        //Reactivate child series
        $this->orthancSeriesRepository->reactivateSeriesOfOrthancStudyID($studyData['orthanc_id']);
        //Update upload status to Done
        $this->visitService->setVisitId($studyData['visit_id']);
        $this->visitService->updateUploadStatus(Constants::UPLOAD_STATUS_DONE);
    }
}
