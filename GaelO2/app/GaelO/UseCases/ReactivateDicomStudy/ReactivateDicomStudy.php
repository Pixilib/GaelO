<?php

namespace App\GaelO\UseCases\ReactivateDicomStudy;

use App\GaelO\Constants\Constants;
use App\GaelO\Constants\Enums\QualityControlStateEnum;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\DicomStudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use App\GaelO\Services\DicomService;
use Exception;

class ReactivateDicomStudy
{

    private VisitRepositoryInterface $visitRepositoryInterface;
    private AuthorizationVisitService $authorizationVisitService;
    private DicomService $dicomService;
    private DicomStudyRepositoryInterface $dicomStudyRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(VisitRepositoryInterface $visitRepositoryInterface, AuthorizationVisitService $authorizationVisitService, DicomService $dicomService, DicomStudyRepositoryInterface $dicomStudyRepositoryInterface,  TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->dicomService = $dicomService;
        $this->dicomStudyRepositoryInterface = $dicomStudyRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function execute(ReactivateDicomStudyRequest $reactivateDicomStudyRequest, ReactivateDicomStudyResponse $reactivateDicomStudyResponse)
    {

        try {


            if (empty($reactivateDicomStudyRequest->reason)) throw new GaelOBadRequestException('Reason must be specified');

            $currentUserId = $reactivateDicomStudyRequest->currentUserId;


            $studyData = $this->dicomStudyRepositoryInterface->getDicomStudy($reactivateDicomStudyRequest->studyInstanceUID, true);
            $visitId = $studyData['visit_id'];

            $visitContext = $this->visitRepositoryInterface->getVisitContext($visitId);
            $studyName = $visitContext['patient']['study_name'];

            if ($reactivateDicomStudyRequest->studyName !== $studyName) {
                throw new GaelOForbiddenException();
            }

            $this->checkAuthorization($currentUserId, $visitId, $studyName, $visitContext);

            //Change dicom study Activation
            $this->dicomService->setCurrentUserId($currentUserId);
            $this->dicomService->reactivateDicomStudy($studyData['study_uid']);

            //Tracker
            $actionDetails = [
                'studyInstanceUID' => $studyData['study_uid'],
                'reason' => $reactivateDicomStudyRequest->reason
            ];

            $this->trackerRepositoryInterface->writeAction(
                $currentUserId,
                Constants::ROLE_SUPERVISOR,
                $studyName,
                $visitId,
                Constants::TRACKER_REACTIVATE_DICOM_STUDY,
                $actionDetails
            );
            $reactivateDicomStudyResponse->status = 200;
            $reactivateDicomStudyResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $reactivateDicomStudyResponse->status = $e->statusCode;
            $reactivateDicomStudyResponse->statusText = $e->statusText;
            $reactivateDicomStudyResponse->body = $e->getErrorBody();
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $userId, int $visitId, string $studyName, array $visitContext): void
    {
        $qcStatus = $visitContext['state_quality_control'];
        //If QC is done, can't reactivate Study
        if (in_array($qcStatus, [QualityControlStateEnum::ACCEPTED->value, QualityControlStateEnum::REFUSED->value])) {
            throw new GaelOForbiddenException();
        }

        $this->authorizationVisitService->setUserId($userId);
        $this->authorizationVisitService->setVisitId($visitId);
        $this->authorizationVisitService->setStudyName($studyName);
        $this->authorizationVisitService->setVisitContext($visitContext);

        if (!$this->authorizationVisitService->isVisitAllowed(Constants::ROLE_SUPERVISOR)) {
            throw new GaelOForbiddenException();
        }
    }
}
