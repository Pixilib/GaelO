<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\OrthancSeriesRepository;
use App\GaelO\Repositories\OrthancStudyRepository;

class DicomSeriesService{

    private OrthancSeriesRepository $orthancSeriesRepository;
    private OrthancStudyRepository $orthancStudyRepository;
    private VisitService $visitService;

    public function __construct(OrthancSeriesRepository $orthancSeriesRepository,
                                OrthancStudyRepository $orthancStudyRepository,
                                VisitService $visitService){

        $this->orthancSeriesRepository = $orthancSeriesRepository;
        $this->orthancStudyRepository = $orthancStudyRepository;
        $this->visitService = $visitService;
    }

    public function deleteSeries(string $seriesInstanceUID, string $role){

        $seriesData = $this->orthancSeriesRepository->getSeriesBySeriesInstanceUID($seriesInstanceUID, false);
        $studyOrthancID = $seriesData['orthancStudy']['orthanc_id'];
        $visitId = $seriesData['orthancStudy']['visit_id'];

        $this->orthancSeriesRepository->deletebySeriesInstanceUID($seriesInstanceUID);

        $remainingSeries = $this->orthancStudyRepository->getChildSeries($studyOrthancID, false);

        if(sizeof($remainingSeries) === 0){
            $this->orthancStudyRepository->delete($seriesData['orthancStudy']['orthanc_id']);
            $this->visitService->updateUploadStatus($visitId, Constants::UPLOAD_STATUS_NOT_DONE);
            //Reset QC only if suppervisor, we don't change QC status for investigator and controller (as it still ongoing)
            if ($role === Constants::ROLE_SUPERVISOR) {
                $this->visitService->resetQc($visitId);
            }
            $this->visitService->updateInvestigatorFormStatus($visitId, Constants::INVESTIGATOR_FORM_DRAFT);
        }

    }

    public function getSeriesBySeriesInstanceUID(string $seriesInstanceUID){
        return $this->orthancSeriesRepository->getSeriesBySeriesInstanceUID($seriesInstanceUID, false);
    }

}
