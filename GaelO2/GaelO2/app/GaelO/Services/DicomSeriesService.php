<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOForbiddenException;
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
        $studyOrthancID = $seriesData['orthanc_study']['orthanc_id'];
        $visitId = $seriesData['orthanc_study']['visit_id'];

        $this->orthancSeriesRepository->deletebySeriesInstanceUID($seriesInstanceUID);

        $remainingSeries = $this->orthancStudyRepository->getChildSeries($studyOrthancID, false);

        if(sizeof($remainingSeries) === 0){
            $this->orthancStudyRepository->delete($seriesData['orthanc_study']['orthanc_id']);
            $this->visitService->updateUploadStatus($visitId, Constants::UPLOAD_STATUS_NOT_DONE);
            //Reset QC only if suppervisor, we don't change QC status for investigator and controller (as it still ongoing)
            if ($role === Constants::ROLE_SUPERVISOR) {
                $this->visitService->resetQc($visitId);
            }
            //SK ICI IL FAUT AVOIR L ENTITY REVIEW ET METTRE EN NON VALIDE LE FORM INVESTIGATOR
            //SK ICI QUE SI ON EST en invesigator form needed dans visit type ?
            //SK Logique a encapsuler dans VisitService
            $this->visitService->updateInvestigatorFormStatus($visitId, Constants::INVESTIGATOR_FORM_DRAFT);
        }

    }

    public function reactivateBySeriesInstanceUID(string $seriesInstanceUID) : void {
        $this->orthancSeriesRepository->reactivateBySeriesInstanceUID($seriesInstanceUID);

    }

    public function getSeriesBySeriesInstanceUID(string $seriesInstanceUID, bool $includeDeleted){
        return $this->orthancSeriesRepository->getSeriesBySeriesInstanceUID($seriesInstanceUID, $includeDeleted);
    }

    public function getStudyByStudyInstanceUID(string $studyInstanceUID, bool $includeDeleted) : array {
        return $this->orthancStudyRepository->getOrthancStudyByStudyInstanceUID($studyInstanceUID, $includeDeleted);
    }

    public function reactivateOrthancStudyByStudyInstanceUID(string $studyInstanceUID) : void {

        //Get data from StudyInstanceUID
        $studyData = $this->orthancStudyRepository->getOrthancStudyByStudyInstanceUID($studyInstanceUID, true);

        //Check no other activated study for this visit
        if ($this->orthancStudyRepository->isExistingDicomStudyForVisit($studyData['visit_id'])){
            throw new GaelOBadRequestException("Already existing Dicom Study for this visit");
        };

        //reactivate study level
        $this->orthancStudyRepository->reactivateByStudyInstanceUID($studyInstanceUID);
        //Reactivate child series
        $this->orthancSeriesRepository->reactivateSeriesOfOrthancStudyID($studyData['orthanc_id']);
        //Update upload status to Done
        $this->visitService->updateUploadStatus($studyData['visit_id'], Constants::UPLOAD_STATUS_DONE);

    }

}
