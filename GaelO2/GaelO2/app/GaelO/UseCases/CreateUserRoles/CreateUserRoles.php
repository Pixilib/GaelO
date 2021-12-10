<?php
namespace App\GaelO\UseCases\CreateUserRoles;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;

class CreateUserRoles {

    private UserRepositoryInterface $userRepositoryInterface;
    private StudyRepositoryInterface $studyRepositoryInterface;
    private AuthorizationUserService $authorizationUserService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(UserRepositoryInterface $userRepositoryInterface, StudyRepositoryInterface $studyRepositoryInterface, AuthorizationUserService $authorizationUserService, TrackerRepositoryInterface $trackerRepositoryInterface){
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->authorizationUserService = $authorizationUserService;
    }

    public function execute(CreateUserRolesRequest $createRoleRequest, CreateUserRolesResponse $createRoleResponse){

        try{

            $this->checkAuthorization($createRoleRequest->currentUserId);

            //Get current roles in study for users
            $actualRolesArray = $this->userRepositoryInterface->getUsersRolesInStudy($createRoleRequest->userId, $createRoleRequest->study);
            $studyEntity = $this->studyRepositoryInterface->find($createRoleRequest->study);

            if( $studyEntity['ancillary_of'] && ! in_array($createRoleRequest->role, [Constants::ROLE_SUPERVISOR, Constants::ROLE_REVIEWER]) ){
                throw new GaelOForbiddenException("For an ancillary study only reviewer and supervisor role are allowed");
            }

            if( in_array($createRoleRequest->role, $actualRolesArray) ) {
                throw new GaelOConflictException("Already Existing Role");
            }

            //Write in database and return sucess response (error will be handled by laravel)
            $this->userRepositoryInterface->addUserRoleInStudy($createRoleRequest->userId, $createRoleRequest->study, $createRoleRequest->role);
            $actionDetails = [
                "Add Roles"=> $createRoleRequest->role
            ];
            $this->trackerRepositoryInterface->writeAction( $createRoleRequest->currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, $createRoleRequest->study, null, Constants::TRACKER_EDIT_USER, $actionDetails);

            $createRoleResponse->statusText = "Created";
            $createRoleResponse->status = 201;

        } catch (GaelOException $e){
            $createRoleResponse->statusText = $e->statusText;
            $createRoleResponse->status = $e->statusCode;
            $createRoleResponse->body = $e->getErrorBody();
        }

    }

    private function checkAuthorization(int $userId) : void {
        $this->authorizationUserService->setUserId($userId);
        if( ! $this->authorizationUserService->isAdmin($userId) ) {
            throw new GaelOForbiddenException();
        };
    }
}
