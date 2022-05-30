<?php

namespace App\GaelO\UseCases\GetDicoms;

use App\GaelO\Constants\Constants;
use App\GaelO\Entities\DicomSeriesEntity;
use App\GaelO\Entities\DicomStudyEntity;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\DicomStudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use Exception;

class GetDicoms
{

    private AuthorizationVisitService $authorizationVisitService;
    private DicomStudyRepositoryInterface $dicomStudyRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;

    public function __construct(DicomStudyRepositoryInterface $dicomStudyRepositoryInterface, VisitRepositoryInterface $visitRepositoryInterface, AuthorizationVisitService $authorizationVisitService)
    {
        $this->dicomStudyRepositoryInterface = $dicomStudyRepositoryInterface;
        $this->authorizationVisitService = $authorizationVisitService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
    }

    public function execute(GetDicomsRequest $getDicomsRequest, GetDicomsResponse $getDicomResponse)
    {
        try {

            $visitId = $getDicomsRequest->visitId;
            $currentUserId = $getDicomsRequest->currentUserId;
            $role = $getDicomsRequest->role;
            $studyName = $getDicomsRequest->studyName;

            $this->checkAuthorization($visitId, $currentUserId, $role, $studyName);

            $visitContext = $this->visitRepositoryInterface->getVisitContext($visitId, false);
            //If Supervisor include deleted studies
            $includeTrashedStudies = $role === Constants::ROLE_SUPERVISOR;
            //Include Trashed Series if Supervisor OR (Investigator and QC pending)
            $includedTrashedSeries = ($role === Constants::ROLE_INVESTIGATOR
                && in_array($visitContext['state_quality_control'], [Constants::QUALITY_CONTROL_CORRECTIVE_ACTION_ASKED, Constants::QUALITY_CONTROL_NOT_DONE]))
                || ($role === Constants::ROLE_SUPERVISOR);

            $data = $this->dicomStudyRepositoryInterface->getDicomsDataFromVisit($visitId, $includeTrashedStudies, $includedTrashedSeries);

            $responseArray = [];

            foreach ($data as $study) {
                $studyEntity = DicomStudyEntity::fillFromDBReponseArray($study);
                $studyEntity->addUploaderDetails($study['uploader']);
                foreach ($study['dicom_series'] as $series) {
                    $studyEntity->series[] = DicomSeriesEntity::fillFromDBReponseArray($series);
                }

                $responseArray[] = $studyEntity;
            }

            $getDicomResponse->status = 200;
            $getDicomResponse->statusText = 'OK';
            $getDicomResponse->body = $responseArray;
        } catch (GaelOException $e) {
            $getDicomResponse->status = $e->statusCode;
            $getDicomResponse->statusText = $e->statusText;
            $getDicomResponse->body = $e->getErrorBody();
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $visitId, int $userId, string $role, string $studyName): void
    {

        $this->authorizationVisitService->setUserId($userId);
        $this->authorizationVisitService->setVisitId($visitId);
        $this->authorizationVisitService->setStudyName($studyName);
        if (!$this->authorizationVisitService->isVisitAllowed($role)) {
            throw new GaelOForbiddenException();
        }
    }
}
