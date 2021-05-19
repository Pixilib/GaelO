<?php

namespace App\GaelO\UseCases\GetDicomsStudy;

use App\GaelO\Constants\Constants;
use App\GaelO\Entities\DicomSeriesEntity;
use App\GaelO\Entities\DicomStudyEntity;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\DicomStudyRepositoryInterface;
use App\GaelO\Services\AuthorizationService;

use Exception;

class GetDicomsStudy
{

    private AuthorizationService $authorizationService;
    private DicomStudyRepositoryInterface $dicomStudyRepositoryInterface;

    public function __construct(
        AuthorizationService $authorizationService,
        DicomStudyRepositoryInterface $dicomStudyRepositoryInterface
    ) {
        $this->authorizationService = $authorizationService;
        $this->dicomStudyRepositoryInterface = $dicomStudyRepositoryInterface;
    }

    public function execute(GetDicomsStudyRequest $getDicomsStudyRequest, GetDicomsStudyResponse $getDicomsStudyResponse)
    {

        try {

            $this->checkAuthorization(
                $getDicomsStudyRequest->currentUserId,
                $getDicomsStudyRequest->studyName,
            );

            $data = $this->dicomStudyRepositoryInterface->getDicomStudyFromStudy($getDicomsStudyRequest->studyName, $getDicomsStudyRequest->withTrashed);


            $answer = [];

            foreach ($data as $study) {
                $dicomStudy = DicomStudyEntity::fillFromDBReponseArray($study);
                $seriesObjectArray = [];

                foreach($study['dicom_series'] as $series){
                    $dicomSeries[] = DicomSeriesEntity::fillFromDBReponseArray($series);
                }

                $dicomStudy->addPatientDetails($study['visit']['patient']);
                $dicomStudy->addVisitDetails($study['visit']);
                $dicomStudy->addDicomSeries($seriesObjectArray);
                $answer[] = $dicomStudy;
            }

            $getDicomsStudyResponse->status = 200;
            $getDicomsStudyResponse->statusText = 'OK';
            $getDicomsStudyResponse->body = $answer;

        } catch (GaelOException $e) {

            $getDicomsStudyResponse->status = $e->statusCode;
            $getDicomsStudyResponse->statusText = $e->statusText;
            $getDicomsStudyResponse->body = $e->getErrorBody();

        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $userId, string $studyName)
    {
        $this->authorizationService->setCurrentUserAndRole($userId, Constants::ROLE_SUPERVISOR);
        if (!$this->authorizationService->isRoleAllowed($studyName)) {
            throw new GaelOForbiddenException();
        };
    }
}
