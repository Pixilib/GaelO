<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\AddAffiliatedCenter\AddAffiliatedCenter;
use App\GaelO\UseCases\AddAffiliatedCenter\AddAffiliatedCenterRequest;
use App\GaelO\UseCases\AddAffiliatedCenter\AddAffiliatedCenterResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\GaelO\UseCases\CreateUser\CreateUser;
use App\GaelO\UseCases\CreateUser\CreateUserRequest;
use App\GaelO\UseCases\CreateUser\CreateUserResponse;

use App\GaelO\UseCases\ModifyUser\ModifyUser;
use App\GaelO\UseCases\ModifyUser\ModifyUserRequest;
use App\GaelO\UseCases\ModifyUser\ModifyUserResponse;

use App\GaelO\UseCases\GetUser\GetUser;
use App\GaelO\UseCases\GetUser\GetUserRequest;
use App\GaelO\UseCases\GetUser\GetUserResponse;

use App\GaelO\UseCases\ChangePassword\ChangePassword;
use App\GaelO\UseCases\ChangePassword\ChangePasswordRequest;
use App\GaelO\UseCases\ChangePassword\ChangePasswordResponse;
use App\GaelO\UseCases\CreateUserRoles\CreateUserRoles;
use App\GaelO\UseCases\CreateUserRoles\CreateUserRolesRequest;
use App\GaelO\UseCases\CreateUserRoles\CreateUserRolesResponse;
use App\GaelO\UseCases\DeleteAffiliatedCenter\DeleteAffiliatedCenter;
use App\GaelO\UseCases\DeleteAffiliatedCenter\DeleteAffiliatedCenterRequest;
use App\GaelO\UseCases\DeleteAffiliatedCenter\DeleteAffiliatedCenterResponse;
use App\GaelO\UseCases\DeleteUser\DeleteUser;
use App\GaelO\UseCases\DeleteUser\DeleteUserRequest;
use App\GaelO\UseCases\DeleteUser\DeleteUserResponse;
use App\GaelO\UseCases\DeleteUserRole\DeleteUserRole;
use App\GaelO\UseCases\DeleteUserRole\DeleteUserRoleRequest;
use App\GaelO\UseCases\DeleteUserRole\DeleteUserRoleResponse;
use App\GaelO\UseCases\GetAffiliatedCenter\GetAffiliatedCenter;
use App\GaelO\UseCases\GetAffiliatedCenter\GetAffiliatedCenterRequest;
use App\GaelO\UseCases\GetAffiliatedCenter\GetAffiliatedCenterResponse;
use App\GaelO\UseCases\GetUserRoles\GetUserRoles;
use App\GaelO\UseCases\GetUserRoles\GetUserRolesRequest;
use App\GaelO\UseCases\GetUserRoles\GetUserRolesResponse;
use App\GaelO\UseCases\ReactivateUser\ReactivateUser;
use App\GaelO\UseCases\ReactivateUser\ReactivateUserRequest;
use App\GaelO\UseCases\ReactivateUser\ReactivateUserResponse;
use App\GaelO\UseCases\GetUserFromStudy\GetUserFromStudy;
use App\GaelO\UseCases\GetUserFromStudy\GetUserFromStudyRequest;
use App\GaelO\UseCases\GetUserFromStudy\GetUserFromStudyResponse;
use App\GaelO\UseCases\ModifyUserIdentification\ModifyUserIdentification;
use App\GaelO\UseCases\ModifyUserIdentification\ModifyUserIdentificationRequest;
use App\GaelO\UseCases\ModifyUserIdentification\ModifyUserIdentificationResponse;
use App\GaelO\Util;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

    public function getUser(int $id=0, GetUserRequest $getUserRequest, GetUserResponse $getUserResponse, GetUser $getUser) {
        $curentUser = Auth::user();
        $getUserRequest->currentUserId = $curentUser['id'];
        $getUserRequest->id = $id;
        $getUser->execute($getUserRequest, $getUserResponse);
        return response()->json($getUserResponse->body)
                ->setStatusCode($getUserResponse->status, $getUserResponse->statusText);
    }

    public function createUser(Request $request, CreateUserRequest $createUserRequest, CreateUserResponse $createUserResponse, CreateUser $createUser) {
        //Get current user requesting the API
        $curentUser = Auth::user();
        //Add current user ID in Request DTO
        $createUserRequest->currentUserId = $curentUser['id'];
        $requestData = $request->all();
        //Fill DTO with all other request data
        $createUserRequest = Util::fillObject($requestData, $createUserRequest);
        //Execute use case
        $createUser->execute($createUserRequest, $createUserResponse);
        //Output result comming from usecase, here no content has to be shown (only http status code and text)
        return response()->noContent()
                ->setStatusCode($createUserResponse->status, $createUserResponse->statusText);
    }

    public function modifyUser(int $id, Request $request, ModifyUserRequest $modifyUserRequest, ModifyUserResponse $modifyUserResponse, ModifyUser $modifyUser) {
        $curentUser = Auth::user();
        $modifyUserRequest->currentUserId = $curentUser['id'];
        $requestData = $request->all();
        $requestData['userId'] = $id;
        $modifyUserRequest = Util::fillObject($requestData, $modifyUserRequest);
        $modifyUser->execute($modifyUserRequest, $modifyUserResponse);
        return response()->noContent()
                ->setStatusCode($modifyUserResponse->status, $modifyUserResponse->statusText);
    }

    public function modifyUserIdentification(int $id, Request $request, ModifyUserIdentificationRequest $modifyUserRequest, ModifyUserIdentificationResponse $modifyUserResponse, ModifyUserIdentification $modifyUser) {
        $curentUser = Auth::user();
        $modifyUserRequest->currentUserId = $curentUser['id'];
        $requestData = $request->all();
        $requestData['userId'] = $id;
        $modifyUserRequest = Util::fillObject($requestData, $modifyUserRequest);
        $modifyUser->execute($modifyUserRequest, $modifyUserResponse);
        return response()->noContent()
                ->setStatusCode($modifyUserResponse->status, $modifyUserResponse->statusText);
    }

    public function changeUserPassword(int $id, Request $request, ChangePasswordRequest $changePasswordRequest, ChangePasswordResponse $changePasswordResponse, ChangePassword $changePassword) {
        $curentUser = Auth::user();
        $changePasswordRequest->currentUserId = $curentUser['id'];
        $requestData = $request->all();
        $requestData['id'] = $id;
        $changePasswordRequest = Util::fillObject($requestData, $changePasswordRequest);
        $changePassword->execute($changePasswordRequest, $changePasswordResponse);
        return response()->noContent()
                ->setStatusCode($changePasswordResponse->status, $changePasswordResponse->statusText);
    }

    public function deleteUser(int $id, Request $request, DeleteUserRequest $deleteUserRequest, DeleteUserResponse $deleteUserResponse, DeleteUser $deleteUser) {
        $user = Auth::user();

        $requestData = get_object_vars($request);
        $deleteUserRequest->id = $id;
        $deleteUserRequest->currentUserId = $user['id'];
        $deleteUserRequest = Util::fillObject($requestData, $deleteUserRequest);
        $deleteUser->execute($deleteUserRequest, $deleteUserResponse);
        return response()->noContent()
                    ->setStatusCode($deleteUserResponse->status, $deleteUserResponse->statusText);
    }

    public function getRoles(int $id, string $study = '', GetUserRolesRequest $getUserRolesRequest, GetUserRolesResponse $getUserRolesResponse, GetUserRoles $getUserRoles){
        $getUserRolesRequest->userId = $id;
        $getUserRolesRequest->study = $study;
        $getUserRoles->execute($getUserRolesRequest, $getUserRolesResponse);
        return response()->json($getUserRolesResponse->body)
                ->setStatusCode($getUserRolesResponse->status, $getUserRolesResponse->statusText);
    }

    public function createRole(int $id, string $study, Request $request, CreateUserRoles $createUserRole, CreateUserRolesRequest $createUserRoleRequest, CreateUserRolesResponse $createUserRoleResponse){
        $curentUser = Auth::user();
        $rolesArray = $request->all();
        $createUserRoleRequest->userId = $id;
        $createUserRoleRequest->study = $study;
        $createUserRoleRequest->currentUserId = $curentUser['id'];
        $createUserRoleRequest->roles = $rolesArray;
        $createUserRole->execute($createUserRoleRequest, $createUserRoleResponse);
        return response()->noContent()
        ->setStatusCode($createUserRoleResponse->status, $createUserRoleResponse->statusText);
    }

    public function deleteRole(int $id, String $study, String $roleName, DeleteUserRole $deleteUserRole, DeleteUserRoleRequest $deleteUserRoleRequest, DeleteUserRoleResponse $deleteUserRoleResponse) {
        $curentUser = Auth::user();
        $deleteUserRoleRequest->currentUserId = $curentUser['id'];
        $deleteUserRoleRequest->userId = $id;
        $deleteUserRoleRequest->study = $study;
        $deleteUserRoleRequest->role = $roleName;
        $deleteUserRole->execute($deleteUserRoleRequest, $deleteUserRoleResponse);

        return response()->noContent()
        ->setStatusCode($deleteUserRoleResponse->status, $deleteUserRoleResponse->statusText);
    }

    public function addAffiliatedCenter(int $userId, Request $request, AddAffiliatedCenter $addAffiliatedCenter, AddAffiliatedCenterRequest $addAffiliatedCenterRequest, AddAffiliatedCenterResponse $addAffiliatedCenterResponse){
        $requestData = $request->all();
        $addAffiliatedCenterRequest = Util::fillObject($requestData, $addAffiliatedCenterRequest);
        $curentUser = Auth::user();
        $addAffiliatedCenterRequest->currentUserId = $curentUser['id'];
        $addAffiliatedCenterRequest->userId = $userId;

        $addAffiliatedCenter->execute($addAffiliatedCenterRequest, $addAffiliatedCenterResponse);

        return response()->noContent()
        ->setStatusCode($addAffiliatedCenterResponse->status, $addAffiliatedCenterResponse->statusText);
    }

    public function getAffiliatedCenter(int $userId, GetAffiliatedCenter $getAffiliatedCenter, GetAffiliatedCenterRequest $getAffiliatedCenterRequest, GetAffiliatedCenterResponse $getAffiliatedCenterResponse){
        $curentUser = Auth::user();
        $getAffiliatedCenterRequest->currentUserId = $curentUser['id'];
        $getAffiliatedCenterRequest->userId = $userId;
        $getAffiliatedCenter->execute($getAffiliatedCenterRequest, $getAffiliatedCenterResponse);

        return response()->json($getAffiliatedCenterResponse->body)
        ->setStatusCode($getAffiliatedCenterResponse->status, $getAffiliatedCenterResponse->statusText);

    }

    public function deleteAffiliatedCenter(int $userId, int $centerCode, DeleteAffiliatedCenter $deleteAffiliatedCenter, DeleteAffiliatedCenterRequest $deleteAffiliatedCenterRequest, DeleteAffiliatedCenterResponse $deleteAffiliatedCenterResponse){
        $curentUser = Auth::user();
        $deleteAffiliatedCenterRequest->currentUserId = $curentUser['id'];
        $deleteAffiliatedCenterRequest->userId = $userId;
        $deleteAffiliatedCenterRequest->centerCode = $centerCode;
        $deleteAffiliatedCenter->execute($deleteAffiliatedCenterRequest, $deleteAffiliatedCenterResponse);

        return response()->noContent()
        ->setStatusCode($deleteAffiliatedCenterResponse->status, $deleteAffiliatedCenterResponse->statusText);

    }

    public function reactivateUser(int $userId, ReactivateUser $reactivateUser, ReactivateUserRequest $reactivateUserRequest, ReactivateUserResponse $reactivateUserResponse){
        $curentUser = Auth::user();
        $reactivateUserRequest->currentUserId = $curentUser['id'];
        $reactivateUserRequest->userId = $userId;
        $reactivateUser->execute($reactivateUserRequest, $reactivateUserResponse);

        return response()->noContent()
        ->setStatusCode($reactivateUserResponse->status, $reactivateUserResponse->statusText);
    }

    public function getUserFromStudy(string $studyName, GetUserFromStudyRequest $getUserFromStudyRequest, GetUserFromStudyResponse $getUserFromStudyResponse, GetUserFromStudy $getUserFromStudy){
        $curentUser = Auth::user();
        $getUserFromStudyRequest->currentUserId = $curentUser['id'];
        $getUserFromStudyRequest->studyName = $studyName;
        $getUserFromStudy->execute($getUserFromStudyRequest, $getUserFromStudyResponse);
        return response()->json($getUserFromStudyResponse->body)
                ->setStatusCode($getUserFromStudyResponse->status, $getUserFromStudyResponse->statusText);
    }

}
