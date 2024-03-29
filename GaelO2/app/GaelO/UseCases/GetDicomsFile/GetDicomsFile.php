<?php

namespace App\GaelO\UseCases\GetDicomsFile;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\DicomStudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use App\GaelO\Services\OrthancService;
use Exception;

class GetDicomsFile
{

    private VisitRepositoryInterface $visitRepositoryInterface;
    private AuthorizationVisitService $authorizationService;
    private DicomStudyRepositoryInterface $dicomStudyRepositoryInterface;
    private OrthancService $orthancService;
    private array $orthancSeriesIDs;

    public function __construct(VisitRepositoryInterface $visitRepositoryInterface, DicomStudyRepositoryInterface $dicomStudyRepositoryInterface, AuthorizationVisitService $authorizationService, OrthancService $orthancService)
    {
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->dicomStudyRepositoryInterface = $dicomStudyRepositoryInterface;
        $this->orthancService = $orthancService;
        $this->authorizationService = $authorizationService;
        $this->orthancService->setOrthancServer(true);
    }

    public function execute(GetDicomsFileRequest $getDicomsRequest, GetDicomsFileResponse $getDicomsResponse)
    {

        try {

            $studyName = $getDicomsRequest->studyName;
            $visitId = $getDicomsRequest->visitId;
            //Visits data
            $visitContext = $this->visitRepositoryInterface->getVisitContext($visitId);
            //Authorization Check
            $this->checkAuthorization($getDicomsRequest->currentUserId, $visitId, $getDicomsRequest->role, $studyName, $visitContext);

            $visitType = $visitContext['visit_type']['name'];
            $visitGroupName =  $visitContext['visit_type']['visit_group']['name'];
            $patientId = $visitContext['patient']['id'];

            //Get SeriesOrthancID from database to be downloaded
            $this->orthancSeriesIDs = $this->getVisitSeriesIdsDicomArray($visitContext['id']);

            //First output the filename, then the controller will call outputStream to get content of orthanc response
            $getDicomsResponse->filename = 'DICOM_' . $studyName . '_' . $visitGroupName . '_' . $visitType . '_' . $patientId . '.zip';
            $getDicomsResponse->status = 200;
            $getDicomsResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $getDicomsResponse->status = $e->statusCode;
            $getDicomsResponse->statusText = $e->statusText;
            $getDicomsResponse->body = $e->getErrorBody();
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $currentUserId, int $visitId, string $role, string $studyName, array $visitContext)
    {
        if ($role === Constants::ROLE_MONITOR) {
            throw new GaelOForbiddenException("Monitor can't donwload Dicom");
        }

        $this->authorizationService->setUserId($currentUserId);
        $this->authorizationService->setVisitId($visitId);
        $this->authorizationService->setStudyName($studyName);
        $this->authorizationService->setVisitContext($visitContext);

        if (!$this->authorizationService->isVisitAllowed($role)) {
            throw new GaelOForbiddenException();
        }
    }


    private function getVisitSeriesIdsDicomArray(int $visitId): array
    {
        $studyInstanceUid = $this->dicomStudyRepositoryInterface->getStudyInstanceUidFromVisit($visitId);
        $seriesEntities = $this->dicomStudyRepositoryInterface->getChildSeries($studyInstanceUid, false);
        $seriesOrthancIdArray = array_map(function ($series) {
            return $series['orthanc_id'];
        }, $seriesEntities);

        return $seriesOrthancIdArray;
    }

    public function outputStream()
    {
        $this->orthancService->getOrthancZipStream($this->orthancSeriesIDs, Constants::IMPLICIT_VR_TSUID);
    }
}
