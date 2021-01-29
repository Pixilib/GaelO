<?php

namespace App\GaelO\UseCases\CreateVisit;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationPatientService;
use App\GaelO\Services\VisitService;
use DateTime;
use Exception;

class CreateVisit {

    private VisitRepositoryInterface $visitRepositoryInterface;
    private AuthorizationPatientService $authorizationService;
    private VisitService $visitService;

    public function __construct(VisitRepositoryInterface $visitRepositoryInterface, AuthorizationPatientService $authorizationService, VisitService $visitService){
        $this->visitService = $visitService;
        $this->authorizationService = $authorizationService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
    }

    public function execute(CreateVisitRequest $createVisitRequest, CreateVisitResponse $createVisitResponse) : void {

        try{

            $this->checkAuthorization($createVisitRequest->currentUserId, $createVisitRequest->role, $createVisitRequest->patientCode);

            if ($createVisitRequest->statusDone === Constants::VISIT_STATUS_NOT_DONE && empty($createVisitRequest->reasonForNotDone) ){
                throw new GaelOBadRequestException('Reason must be specified is visit not done');
            }

            $existingVisit = $this->visitRepositoryInterface->isExistingVisit(
                            $createVisitRequest->patientCode,
                            $createVisitRequest->visitTypeId);

            if($existingVisit) {
                throw new GaelOConflictException('Visit Already Created');
            }else{

                if($createVisitRequest->visitDate !== null){

                    //Input date should be in SQL FORMAT YYYY-MM-DD
                    if ( !  $this->validateDate($createVisitRequest->visitDate) ){
                        throw new GaelOBadRequestException("Visit Date should be in YYYY-MM-DD and valid");
                    }
                }

                $this->visitService->createVisit(
                    $createVisitRequest->studyName,
                    $createVisitRequest->currentUserId,
                    $createVisitRequest->patientCode,
                    $createVisitRequest->visitDate,
                    $createVisitRequest->visitTypeId,
                    $createVisitRequest->statusDone,
                    $createVisitRequest->reasonForNotDone);

                $createVisitResponse->status = 201;
                $createVisitResponse->statusText = 'Created';
            }
        } catch (GaelOException $e){

            $createVisitResponse->status = $e->statusCode;
            $createVisitResponse->statusText = $e->statusText;
            $createVisitResponse->body = $e->getErrorBody();

        } catch (Exception $e){
            throw $e;
        }


    }

    private function checkAuthorization(int $userId, string $role, int $patientCode) : void{
        $this->authorizationService->setCurrentUserAndRole($userId, $role);
        $this->authorizationService->setPatient($patientCode);
        if (! $this->authorizationService->isPatientAllowed() ){
            throw new GaelOForbiddenException();
        }


    }

    private function validateDate(string $date, $format = 'Y-m-d'){
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }


}
