<?php

namespace App\GaelO\UseCases\CreateVisit;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationPatientService;
use App\GaelO\Services\CreateVisitService;
use App\GaelO\Services\GaelOStudiesService\AbstractGaelOStudy;
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
            $statusDone = $createVisitRequest->statusDone;
            $visitDate = $createVisitRequest->visitDate;
            $reasonForNotDone = $createVisitRequest->reasonForNotDone;
            $visitTypeId = $createVisitRequest->visitTypeId;
            $studyName = $createVisitRequest->studyName;

            $patientEntity = $this->patientRepositoryInterface->find($patientId);
            $patientCode = $patientEntity['code'];

            if (!in_array($patientEntity['inclusion_status'], [Constants::PATIENT_INCLUSION_STATUS_INCLUDED, Constants::PATIENT_INCLUSION_STATUS_PRE_INCLUDED])) {
                throw new GaelOForbiddenException("Visit Creation only allowed for preincluded or included patient");
            }

            if($studyName !== $patientEntity['study_name']){
                throw new GaelOForbiddenException("Visit Creation only for patient's orignial study");
            }
            
            $this->checkAuthorization($currentUserId, $patientId, $studyName, $role);

            //If visit was not done, force visitDate to null
            if ($statusDone === Constants::VISIT_STATUS_NOT_DONE && !empty($visitDate)) {
                throw new GaelOBadRequestException('Visit Date should not be specified for visit status done');
            }

            if ($statusDone === Constants::VISIT_STATUS_NOT_DONE && empty($reasonForNotDone)) {
                throw new GaelOBadRequestException('Reason must be specified is visit not done');
            }

            $existingVisit = $this->visitRepositoryInterface->isExistingVisit(
                $patientId,
                $visitTypeId
            );

            if ($existingVisit) {
                throw new GaelOConflictException('Visit Already Created');
            }

            //Checking that requested creation is available in the creatable visit type
            //Get specific Study Object
            $studyObject = AbstractGaelOStudy::getSpecificStudyObject($studyName);
            $availableVisitType = $studyObject->getCreatableVisitCalculator()->getAvailableVisitToCreate($patientEntity);
            //put available visitType Id in an array
            $avaibleVisitTypeId = array_map(function($visitType){return $visitType['id'];}, $availableVisitType);

            if(!in_array($visitTypeId, $avaibleVisitTypeId)){
                throw new GaelOForbiddenException('Forbidden Visit Type Creation');
            }

            if ($visitDate !== null) {

                //Input date should be in SQL FORMAT YYYY-MM-DD
                if (!$this->validateDate($visitDate)) {
                    throw new GaelOBadRequestException("Visit Date should be in YYYY-MM-DD and valid");
                }
            }

            $visitId = $this->createVisitSerice->createVisit(
                $studyName,
                $currentUserId,
                $patientId,
                $visitDate,
                $visitTypeId,
                $statusDone,
                $reasonForNotDone
            );

            if ($createVisitRequest->statusDone === Constants::VISIT_STATUS_NOT_DONE) {
                $visitContext = $this->visitRepositoryInterface->getVisitContext($visitId);

                $visitType = $visitContext['visit_type']['name'];

                $this->mailServices->sendVisitNotDoneMessage(
                    $visitId,
                    $studyName,
                    $patientId,
                    $patientCode,
                    $visitType,
                    $reasonForNotDone,
                    $currentUserId
                );
            }

            $details = [
                'visit_date' =>  $visitDate,
                'status_done' => $statusDone,
                'reason_for_not_done' => $reasonForNotDone
            ];

            $this->trackerRepositoryInterface->writeAction(
                $currentUserId,
                $role,
                $studyName,
                $visitId,
                Constants::TRACKER_CREATE_VISIT,
                $details
            );

            $createVisitResponse->body = ['id' => $visitId];
            $createVisitResponse->status = 201;
            $createVisitResponse->statusText = 'Created';
        } catch (AbstractGaelOException $e) {
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
        if ($this->authorizationPatientService->getAuthorizationStudyService()->getStudyEntity()->isAncillaryStudy()) {
            throw new GaelOForbiddenException("Forbidden for ancillaries study");
        }
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
