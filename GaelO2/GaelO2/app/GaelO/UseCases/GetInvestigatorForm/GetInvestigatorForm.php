<?php

namespace App\GaelO\UseCases\GetInvestigatorForm;

use App\GaelO\Entities\InvestigatorFormEntity;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Services\AuthorizationVisitService;
use Exception;

class GetInvestigatorForm{

    private AuthorizationVisitService $authorizationVisitService;
    private ReviewRepositoryInterface $reviewRepositoryInterface;
    private UserRepositoryInterface $userRepositoryInterface;

    //SK AJOUTER LA POSSIBILITE DE VOIR LES REVIEW DELETED PAR LE SUPERVISOR ?
    public function __construct(ReviewRepositoryInterface $reviewRepositoryInterface, AuthorizationVisitService $authorizationVisitService, UserRepositoryInterface $userRepositoryInterface)
    {
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
        $this->authorizationVisitService = $authorizationVisitService;
        $this->userRepositoryInterface = $userRepositoryInterface;
    }

    public function execute(GetInvestigatorFormRequest $getInvestigatorFormRequest, GetInvestigatorFormResponse $getInvestigatorFormResponse){

        try{

            $this->checkAuthorization($getInvestigatorFormRequest->visitId, $getInvestigatorFormRequest->currentUserId, $getInvestigatorFormRequest->role);
            $investigatorFormEntity = $this->reviewRepositoryInterface->getInvestigatorForm($getInvestigatorFormRequest->visitId);

            $user = $this->userRepositoryInterface->find($investigatorFormEntity['user_id']);

            $investigatorForm = InvestigatorFormEntity::fillFromDBReponseArray($investigatorFormEntity);
            $investigatorForm->setInvestigatorDetails($user['username']);

            $getInvestigatorFormResponse->body = $investigatorForm;
            $getInvestigatorFormResponse->status = 200;
            $getInvestigatorFormResponse->statusText = 'OK';

        } catch(GaelOException $e){

            $getInvestigatorFormResponse->body = $e->getErrorBody();
            $getInvestigatorFormResponse->status = $e->statusCode;
            $getInvestigatorFormResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
        }
    }

    private function checkAuthorization(int $visitId, int $currentUserId, string $role){
        $this->authorizationVisitService->setCurrentUserAndRole($currentUserId, $role);
        $this->authorizationVisitService->setVisitId($visitId);
        if ( ! $this->authorizationVisitService->isVisitAllowed() ){
            throw new GaelOForbiddenException();
        }

    }

}
