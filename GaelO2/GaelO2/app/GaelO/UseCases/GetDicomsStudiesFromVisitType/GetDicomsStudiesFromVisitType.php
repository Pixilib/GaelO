<?php

namespace App\GaelO\UseCases\GetDicomsStudiesFromVisitType;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\DicomStudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Entities\DicomSeriesEntity;
use App\GaelO\Entities\DicomStudyEntity;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use Exception;

class GetDicomsStudiesFromVisitType
{

    private AuthorizationStudyService $authorizationStudyService;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private DicomStudyRepositoryInterface $dicomStudyRepositoryInterface;

    public function __construct(AuthorizationStudyService $authorizationStudyService, VisitRepositoryInterface $visitRepositoryInterface, DicomStudyRepositoryInterface $dicomStudyRepositoryInterface)
    {
        $this->authorizationStudyService = $authorizationStudyService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->dicomStudyRepositoryInterface = $dicomStudyRepositoryInterface;
    }

    public function execute(GetDicomsStudiesFromVisitTypeRequest $getDicomsStudiesFromVisitTypeRequest, GetDicomsStudiesFromVisitTypeResponse $getDicomsStudiesFromVisitTypeResponse)
    {

        try {

            $studyName = $getDicomsStudiesFromVisitTypeRequest->studyName;
            $this->checkAuthorization($getDicomsStudiesFromVisitTypeRequest->currentUserId, $studyName);

            //Get Visits in the asked visitTypeId
            $visits = $this->visitRepositoryInterface->getVisitsInVisitType($getDicomsStudiesFromVisitTypeRequest->visitTypeId, false, null, false);
            //make visitsId array
            $visitsId = array_map(function ($visit) {
                return $visit['id'];
            }, $visits);

            //Get Validated review for these visits
            $dicomStudies = $this->dicomStudyRepositoryInterface->getDicomStudyFromVisitIdArrayWithSeries($visitsId, $studyName, false);

            $answer = [];

            foreach ($dicomStudies as $dicomStudy) {

                $dicomStudyEntity = DicomStudyEntity::fillFromDBReponseArray($dicomStudy);

                $dicomSeries = $dicomStudy['dicom_series'];

                //Add nested series entities
                $dicomSeriesEntities = array_map(function ($series) {
                    return DicomSeriesEntity::fillFromDBReponseArray($series);
                }, $dicomSeries);

                $dicomStudyEntity->addDicomSeries($dicomSeriesEntities);
                $dicomStudyEntity->addPatientDetails($dicomStudy['visit']['patient']);
                $dicomStudyEntity->addVisitDetails($dicomStudy['visit']);

                $answer[] = $dicomStudyEntity;
            }

            $getDicomsStudiesFromVisitTypeResponse->body = $answer;
            $getDicomsStudiesFromVisitTypeResponse->status = 200;
            $getDicomsStudiesFromVisitTypeResponse->statusText = 'OK';

        } catch (GaelOException $e) {

            $getDicomsStudiesFromVisitTypeResponse->body = $e->getErrorBody();
            $getDicomsStudiesFromVisitTypeResponse->status = $e->statusCode;
            $getDicomsStudiesFromVisitTypeResponse->statusText = $e->statusText;

        } catch (Exception $e) {
            throw $e;
        }
    }


    private function checkAuthorization(int $currentUserId, string $studyName)
    {
        $this->authorizationStudyService->setStudyName($studyName);
        $this->authorizationStudyService->setUserId($currentUserId);
        if (!$this->authorizationStudyService->isAllowedStudy(Constants::ROLE_SUPERVISOR)) {
            throw new GaelOForbiddenException();
        }
    }
}
