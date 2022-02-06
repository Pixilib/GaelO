<?php

namespace App\GaelO\UseCases\CreateVisit;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationPatientService;
use App\GaelO\Services\CreateVisitService;
use App\GaelO\Services\MailServices;
use DateTime;
use Exception;

class CreateVisit
{

    private PatientRepositoryInterface $patientRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private AuthorizationPatientService $authorizationPatientService;
    private CreateVisitService $createVisitSerice;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private MailServices $mailServices;

    public function __construct(VisitRepositoryInterface $visitRepositoryInterface, PatientRepositoryInterface $patientRepositoryInterface, AuthorizationPatientService $authorizationPatientService, CreateVisitService $createVisitSerice, TrackerRepositoryInterface $trackerRepositoryInterface, MailServices $mailServices)
    {
        $this->createVisitSerice = $createVisitSerice;
        $this->authorizationPatientService = $authorizationPatientService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->patientRepositoryInterface = $patientRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->mailServices = $mailServices;
    }

    public function execute(CreateVisitRequest $createVisitRequest, CreateVisitResponse $createVisitResponse): void
    {

        try {

            $patientId = $createVisitRequest->patientId;
            $currentUserId = $createVisitRequest->currentUserId;
            $role = $createVisitRequest->role;

            $patientEntity = $this->patientRepositoryInterface->find($patientId);

            $studyName = $patientEntity['study_name'];
            $this->checkAuthorization($currentUserId, $patientId, $studyName, $role);

            //If visit was not done, force visitDate to null
            if ($createVisitRequest->statusDone === Constants::VISIT_STATUS_NOT_DONE && !empty($createVisitRequest->visitDate)) {
                throw new GaelOBadRequestException('Visit Date should not be specified for visit status done');
            }

            if ($createVisitRequest->statusDone === Constants::VISIT_STATUS_NOT_DONE && empty($createVisitRequest->reasonForNotDone)) {
                throw new GaelOBadRequestException('Reason must be specified is visit not done');
            }

            $existingVisit = $this->visitRepositoryInterface->isExistingVisit(
                $patientId,
                $createVisitRequest->visitTypeId
            );

            if ($existingVisit) {
                throw new GaelOConflictException('Visit Already Created');
            }

            if ($createVisitRequest->visitDate !== null) {

                //Input date should be in SQL FORMAT YYYY-MM-DD
                if (!$this->validateDate($createVisitRequest->visitDate)) {
                    throw new GaelOBadRequestException("Visit Date should be in YYYY-MM-DD and valid");
                }
            }

            $visitId = $this->createVisitSerice->createVisit(
                $studyName,
                $currentUserId,
                $patientId,
                $createVisitRequest->visitDate,
                $createVisitRequest->visitTypeId,
                $createVisitRequest->statusDone,
                $createVisitRequest->reasonForNotDone
            );

            if ($createVisitRequest->statusDone === Constants::VISIT_STATUS_NOT_DONE) {
                $visitContext = $this->visitRepositoryInterface->getVisitContext($visitId);

                $visitType = $visitContext['visit_type']['name'];

                $this->mailServices->sendVisitNotDoneMessage(
                    $visitId,
                    $studyName,
                    $patientId,
                    $visitType,
                    $createVisitRequest->reasonForNotDone,
                    $currentUserId
                );
            }

            $details = [
                'visit_date' =>  $createVisitRequest->visitDate,
                'status_done' => $createVisitRequest->statusDone,
                'reason_for_not_done' => $createVisitRequest->reasonForNotDone
            ];

            $this->trackerRepositoryInterface->writeAction(
                $currentUserId,
                Constants::ROLE_INVESTIGATOR,
                $studyName,
                $visitId,
                Constants::TRACKER_CREATE_VISIT,
                $details
            );

            $createVisitResponse->body = ['id' => $visitId];
            $createVisitResponse->status = 201;
            $createVisitResponse->statusText = 'Created';
        } catch (GaelOException $e) {

            $createVisitResponse->status = $e->statusCode;
            $createVisitResponse->statusText = $e->statusText;
            $createVisitResponse->body = $e->getErrorBody();
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $userId, string $patientId, string $studyName, string $role): void
    {
        $this->authorizationPatientService->setUserId($userId);
        $this->authorizationPatientService->setStudyName($studyName);
        $this->authorizationPatientService->setPatientId($patientId);

        if (!in_array($role, [Constants::ROLE_INVESTIGATOR, Constants::ROLE_SUPERVISOR]) || !$this->authorizationPatientService->isPatientAllowed($role)) {
            throw new GaelOForbiddenException();
        }
    }

    private function validateDate(string $date, $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
}
