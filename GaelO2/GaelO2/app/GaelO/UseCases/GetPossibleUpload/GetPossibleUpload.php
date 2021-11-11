<?php

namespace App\GaelO\UseCases\GetPossibleUpload;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;

class GetPossibleUpload
{

    private VisitRepositoryInterface $visitRepositoryInterface;
    private UserRepositoryInterface $userRepositoryInterface;
    private AuthorizationService $authorizationService;

    public function __construct(AuthorizationService $authorizationService,  VisitRepositoryInterface $visitRepositoryInterface, UserRepositoryInterface $userRepositoryInterface)
    {
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(GetPossibleUploadRequest $getPossibleUploadRequest, GetPossibleUploadResponse $getPossibleUploadResponse)
    {
        try {

            $this->checkAuthorization($getPossibleUploadRequest->currentUserId, $getPossibleUploadRequest->studyName);

            $centers = $this->userRepositoryInterface->getAllUsersCenters($getPossibleUploadRequest->currentUserId);
            $visitsEntities = $this->visitRepositoryInterface->getImagingVisitsAwaitingUpload($getPossibleUploadRequest->studyName, $centers);

            $answerArray = [];

            foreach ($visitsEntities as $visit) {
                $item['patientId'] = $visit['patient_id'];
                $item['patientFirstname'] = $visit['patient']['firstname'];
                $item['patientLastname'] = $visit['patient']['lastname'];
                $item['patientSex'] = $visit['patient']['gender'];
                $item['patientDOB'] = $this->formatBirthDateUS($visit['patient']['birth_month'], $visit['patient']['birth_day'], $visit['patient']['birth_year']);
                $item['visitDate'] = date('m-d-Y', strtotime($visit['visit_date']));
                $item['visitModality'] = $visit['visit_type']['visit_group']['modality'];
                $item['visitType'] = $visit['visit_type']['name'];
                $item['visitTypeID'] = $visit['visit_type']['id'];
                $item['visitID'] = $visit['id'];
                $item['dicomConstraints'] = $visit['visit_type']['dicom_constraints'];
                $answerArray[] = $item;
            }

            $getPossibleUploadResponse->body = $answerArray;
            $getPossibleUploadResponse->status = 200;
            $getPossibleUploadResponse->statusText = 'OK';

        } catch (GaelOException $e) {

            $getPossibleUploadResponse->body = $e->getErrorBody();
            $getPossibleUploadResponse->status = $e->statusCode;
            $getPossibleUploadResponse->statusText = $e->statusText;

        } catch (Exception $e) {

            throw $e;

        }
    }

    private function checkAuthorization(int $userId, string $studyName) : void
    {
        $this->authorizationService->setCurrentUserAndRole($userId, Constants::ROLE_INVESTIGATOR);
        if( ! $this->authorizationService->isRoleAllowed($studyName) ){
            throw new GaelOForbiddenException();
        };
    }

    private function formatBirthDateUS(?int $month, ?int $day, ?int $year): string
    {
        if (empty($month)) {
            $month = 0;
        }
        if (empty($day)) {
            $day = 0;
        }
        if (empty($year)) {
            $year = 0;
        }

        return sprintf("%02d", $month) . '-' . sprintf("%02d", $day) . '-' . $year;
    }
}
