<?php

namespace App\GaelO\UseCases\GetCentersFromStudy;

use App\GaelO\Entities\CenterEntity;
use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\CenterRepositoryInterface;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;

class GetCentersFromStudy {

    private CenterRepositoryInterface $centerRepositoryInterface;
    
    private AuthorizationService $authorizationService;

    public function __construct(CenterRepositoryInterface $centerRepositoryInterface, 
        AuthorizationService $authorizationService,
        PatientRepositoryInterface $patientRepositoryInterface){
        $this->centerRepositoryInterface = $centerRepositoryInterface;
        $this->patientRepositoryInterface = $patientRepositoryInterface;
        $this->authorizationService = $authorizationService;
     }

    public function execute(GetCentersFromStudyRequest $getCentersFromStudyRequest, GetCentersFromStudyResponse $getCentersFromStudyResponse) : void
    {
        try {

            $this->checkAuthorization($getCentersFromStudyRequest->currentUserId, $getCentersFromStudyRequest->studyName);

            $studyName = $getCentersFromStudyRequest->studyName;

            $patients = $this->patientRepositoryInterface->getPatientsInStudy($studyName);
            $centerCodes = [];

            foreach($patients as $patient) {
                if(!in_array($patient['center_code'], $centerCodes)) {
                    $center = $this->centerRepositoryInterface->getCenterByCode($patient['center_code']);
                    $centerCodes[] = $center['code'];    
                }
            }

            $getCentersFromStudyResponse->body = $centerCodes;
            $getCentersFromStudyResponse->status = 200;
            $getCentersFromStudyResponse->statusText = 'OK';

        } catch (GaelOException $e) {
            $getCentersFromStudyResponse->status = $e->statusCode;
            $getCentersFromStudyResponse->statusText = $e->statusText;
            $getCentersFromStudyResponse->body = $e->getErrorBody();

        } catch (Exception $e){
            throw $e;
        }

    }

    private function checkAuthorization(int $currentUserId, string $studyName){
        $this->authorizationService->setCurrentUserAndRole($currentUserId, Constants::ROLE_SUPERVISOR);
        if ( ! $this->authorizationService->isRoleAllowed($studyName)){
            throw new GaelOForbiddenException();
        };
    }

}

?>
