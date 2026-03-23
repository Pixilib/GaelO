<?php

namespace App\GaelO\UseCases\GetStudyStatistics;

use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class GetStudyStatistics
{

    private AuthorizationUserService $authorizationUserService;
    private StudyRepositoryInterface $studyRepositoryInterface;

    public function __construct(AuthorizationUserService $authorizationUserService, StudyRepositoryInterface $studyRepositoryInterface)
    {
        $this->authorizationUserService = $authorizationUserService;
        $this->studyRepositoryInterface = $studyRepositoryInterface;
    }

    public function execute(GetStudyStatisticsRequest $getStudyStatisticsRequest, GetStudyStatisticsResponse $getStudyStatisticsResponse)
    {

        try {
            $this->checkAuthorization($getStudyStatisticsRequest->currentUserId);

            $studyStatistics = $this->studyRepositoryInterface->getStudyStatistics($getStudyStatisticsRequest->studyName, $getStudyStatisticsRequest->withTrashed);

            $getStudyStatisticsResponse->body = [
                'patientsCount' => $studyStatistics['patients_count'],
                'visitsCount' => $studyStatistics['visits_count'],
                'dicomStudiesCount' => $studyStatistics['dicom_studies_count'],
                'dicomSeriesCount' => $studyStatistics['dicom_series_count'],
                'dicomInstancesCount' => $studyStatistics['dicom_instances_count'],
                'dicomDiskSizeBytes' => $studyStatistics['dicom_disk_size']
            ];
            $getStudyStatisticsResponse->status = 200;
            $getStudyStatisticsResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $getStudyStatisticsResponse->body = $e->getErrorBody();
            $getStudyStatisticsResponse->status = $e->statusCode;
            $getStudyStatisticsResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    //Allowed if user is administrator
    private function checkAuthorization(int $userId)
    {

        $this->authorizationUserService->setUserId($userId);
        if (!$this->authorizationUserService->isAdmin()) {
            throw new GaelOForbiddenException();
        }
    }
}
