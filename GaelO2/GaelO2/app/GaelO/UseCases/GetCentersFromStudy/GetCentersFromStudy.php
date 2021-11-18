<?php

namespace App\GaelO\UseCases\GetCentersFromStudy;
use App\GaelO\Constants\Constants;
use App\GaelO\Entities\CenterEntity;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\CenterRepositoryInterface;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class GetCentersFromStudy {

    private AuthorizationUserService $authorizationUserService;
    private PatientRepositoryInterface $patientRepositoryInterface;
    private CenterRepositoryInterface $centerRepositoryInterface;


    public function __construct(CenterRepositoryInterface $centerRepositoryInterface,
        AuthorizationUserService $authorizationUserService,
        PatientRepositoryInterface $patientRepositoryInterface){
        $this->centerRepositoryInterface = $centerRepositoryInterface;
        $this->patientRepositoryInterface = $patientRepositoryInterface;
        $this->authorizationUserService = $authorizationUserService;
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
        $this->authorizationUserService->setUserId($currentUserId);
        if ( ! $this->authorizationUserService->isRoleAllowed(Constants::ROLE_SUPERVISOR, $studyName)){
            throw new GaelOForbiddenException();
        };
    }

}

?>
