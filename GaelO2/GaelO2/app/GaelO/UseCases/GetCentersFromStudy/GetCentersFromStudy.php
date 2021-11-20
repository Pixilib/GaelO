<?php

namespace App\GaelO\UseCases\GetCentersFromStudy;
use App\GaelO\Constants\Constants;
use App\GaelO\Entities\CenterEntity;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\CenterRepositoryInterface;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use Exception;

class GetCentersFromStudy {

    private AuthorizationStudyService $authorizationStudyService;
    private PatientRepositoryInterface $patientRepositoryInterface;
    private CenterRepositoryInterface $centerRepositoryInterface;


    public function __construct(CenterRepositoryInterface $centerRepositoryInterface,
        AuthorizationStudyService $authorizationStudyService,
        PatientRepositoryInterface $patientRepositoryInterface){
        $this->centerRepositoryInterface = $centerRepositoryInterface;
        $this->patientRepositoryInterface = $patientRepositoryInterface;
        $this->authorizationStudyService = $authorizationStudyService;
     }

    public function execute(GetCentersFromStudyRequest $getCentersFromStudyRequest, GetCentersFromStudyResponse $getCentersFromStudyResponse) : void
    {
        try {

            $this->checkAuthorization($getCentersFromStudyRequest->currentUserId, $getCentersFromStudyRequest->studyName);

            $studyName = $getCentersFromStudyRequest->studyName;

            $patients = $this->patientRepositoryInterface->getPatientsInStudy($studyName);
            $centerCodes = array_column($patients, 'center_code');

            $centers = $this->centerRepositoryInterface->getCentersFromCodeArray($centerCodes);
            $responseArray = [];
            foreach($centers as $centerEntity) {
                $responseArray[] = CenterEntity::fillFromDBReponseArray($centerEntity);
            }
            $getCentersFromStudyResponse->body = $responseArray;
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
        $this->authorizationStudyService->setUserId($currentUserId);
        $this->authorizationStudyService->setStudyName($studyName);
        if ( ! $this->authorizationStudyService->isAllowedStudy(Constants::ROLE_SUPERVISOR)){
            throw new GaelOForbiddenException();
        };
    }

}
