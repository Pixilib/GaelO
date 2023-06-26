<?php

namespace App\GaelO\UseCases\GetNiftiFileSupervisor;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\DicomSeriesRepositoryInterface;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use App\GaelO\Services\OrthancService;
use Exception;

class GetNiftiFileSupervisor
{

    private AuthorizationStudyService $authorizationStudyService;
    private DicomSeriesRepositoryInterface $dicomSeriesRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private StudyRepositoryInterface $studyRepositoryInterface;
    private OrthancService $orthancService;

    private string $seriesOrthancID;
    private bool $compress;

    public function __construct(
        AuthorizationStudyService $authorizationStudyService,
        VisitRepositoryInterface $visitRepositoryInterface,
        StudyRepositoryInterface $studyRepositoryInterface,
        DicomSeriesRepositoryInterface $dicomSeriesRepositoryInterface,
        OrthancService $orthancService
    ) {
        $this->authorizationStudyService = $authorizationStudyService;
        $this->dicomSeriesRepositoryInterface = $dicomSeriesRepositoryInterface;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->orthancService = $orthancService;
        $this->orthancService->setOrthancServer(true);
    }

    public function execute(GetNiftiFileSupervisorRequest $getNiftiFileSupervisorRequest, GetNiftiFileSupervisorResponse $getNiftiFileSupervisorResponse)
    {

        try {
            $currentUserId = $getNiftiFileSupervisorRequest->currentUserId;
            $studyName = $getNiftiFileSupervisorRequest->studyName;
            $seriesInstanceUID = $getNiftiFileSupervisorRequest->seriesInstanceUID;
            $compress = $getNiftiFileSupervisorRequest->compress;

            //Get Related visit ID of requested seriesInstanceUID
            $orthancSeriesIds = $this->dicomSeriesRepositoryInterface->getSeriesOrthancIDOfSeriesInstanceUID([$seriesInstanceUID], true);
            $visitIds = $this->dicomSeriesRepositoryInterface->getRelatedVisitIdFromSeriesInstanceUID([$seriesInstanceUID], true);

            //Get Contexts of the related visit
            $visitContext = $this->visitRepositoryInterface->getVisitContext($visitIds[0]);
            $originalStudyName = $visitContext['patient']['study_name'];
            $visitType = $visitContext['visit_type']['name'];
            $visitGroupName =  $visitContext['visit_type']['visit_group']['name'];
            $patientCode = $visitContext['patient']['code'];


            //Get original studyname if called study is an ancillary one
            $requestedStudyName = $this->studyRepositoryInterface->find($studyName);
            $originalRequestedStudyName = $requestedStudyName->getOriginalStudyName();

            //called original study and dicom original study shall be identical
            if ($originalStudyName !== $originalRequestedStudyName) throw new GaelOForbiddenException("Requested Study in not original or ancillary study of these dicoms");

            //Check that currentUser is Supervisor in this study
            $this->checkAuthorization($currentUserId, $studyName);

            $this->seriesOrthancID = $orthancSeriesIds[0];
            $this->compress = $compress;

            //First output the filename, then the controller will call outputStream to get content of orthanc response
            $getNiftiFileSupervisorResponse->filename = 'NIFTI_' . $studyName . '_' . $visitGroupName . '_' . $visitType . '_' . $patientCode . ($compress ? '.nii.gz' : '.nii');
            $getNiftiFileSupervisorResponse->status = 200;
            $getNiftiFileSupervisorResponse->statusText = 'OK';

            
        } catch (AbstractGaelOException $e) {
            $getNiftiFileSupervisorResponse->status = $e->statusCode;
            $getNiftiFileSupervisorResponse->statusText = $e->statusText;
            $getNiftiFileSupervisorResponse->body = $e->getErrorBody();
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $currentUserId, string $studyName)
    {
        $this->authorizationStudyService->setUserId($currentUserId);
        $this->authorizationStudyService->setStudyName($studyName);
        if (!$this->authorizationStudyService->isAllowedStudy(Constants::ROLE_SUPERVISOR)) {
            throw new GaelOForbiddenException();
        }
    }

    public function outputStream()
    {
        $this->orthancService->getOrthancNiftiStream($this->seriesOrthancID, $this->compress);
    }
}
