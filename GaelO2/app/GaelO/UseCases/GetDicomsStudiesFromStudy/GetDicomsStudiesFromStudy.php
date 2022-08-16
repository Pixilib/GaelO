<?php

namespace App\GaelO\UseCases\GetDicomsStudiesFromStudy;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\DicomStudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Entities\DicomSeriesEntity;
use App\GaelO\Entities\DicomStudyEntity;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use Exception;

class GetDicomsStudiesFromStudy
{
    private AuthorizationStudyService $authorizationStudyService;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private DicomStudyRepositoryInterface $dicomStudyRepositoryInterface;
    private StudyRepositoryInterface $studyRepositoryInterface;

    public function __construct(
        AuthorizationStudyService $authorizationStudyService,
        VisitRepositoryInterface $visitRepositoryInterface,
        DicomStudyRepositoryInterface $dicomStudyRepositoryInterface,
        StudyRepositoryInterface $studyRepositoryInterface
    ) {
        $this->authorizationStudyService = $authorizationStudyService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->dicomStudyRepositoryInterface = $dicomStudyRepositoryInterface;
        $this->studyRepositoryInterface = $studyRepositoryInterface;
    }

    public function execute(GetDicomsStudiesFromStudyRequest $getDicomsStudiesFromStudyRequest, GetDicomsStudiesFromStudyResponse $getDicomsStudiesFromStudyResponse)
    {

        try {

            $studyName = $getDicomsStudiesFromStudyRequest->studyName;
            $this->checkAuthorization($getDicomsStudiesFromStudyRequest->currentUserId, $studyName);

            //Retrieve study information, in case being an ancillary study we need to retrieve original study dicom
            $studyEntity = $this->studyRepositoryInterface->find($studyName);
            $originalStudyName = $studyEntity->getOriginalStudyName();

            //Get Visits in the asked study
            $visits = $this->visitRepositoryInterface->getVisitsInStudy($originalStudyName, false, false, false);
            //make visitsId array
            $visitsIds = array_map(function ($visit) {
                return $visit['id'];
            }, $visits);

            //Get Validated review for these visits
            $dicomStudies = $this->dicomStudyRepositoryInterface->getDicomStudyFromVisitIdArrayWithSeries($visitsIds, $getDicomsStudiesFromStudyRequest->withTrashed);

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

            $getDicomsStudiesFromStudyResponse->body = $answer;
            $getDicomsStudiesFromStudyResponse->status = 200;
            $getDicomsStudiesFromStudyResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $getDicomsStudiesFromStudyResponse->body = $e->getErrorBody();
            $getDicomsStudiesFromStudyResponse->status = $e->statusCode;
            $getDicomsStudiesFromStudyResponse->statusText = $e->statusText;
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
