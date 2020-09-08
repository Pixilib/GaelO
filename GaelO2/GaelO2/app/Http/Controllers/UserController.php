<?php

namespace App\Http\Controllers;

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
use App\GaelO\UseCases\DeleteUser\DeleteUser;
use App\GaelO\UseCases\DeleteUser\DeleteUserRequest;
use App\GaelO\UseCases\DeleteUser\DeleteUserResponse;
use App\GaelO\UseCases\DeleteUserRole\DeleteUserRole;
use App\GaelO\UseCases\DeleteUserRole\DeleteUserRoleRequest;
use App\GaelO\UseCases\DeleteUserRole\DeleteUserRoleResponse;
use App\GaelO\UseCases\GetUserRoles\GetUserRoles;
use App\GaelO\UseCases\GetUserRoles\GetUserRolesRequest;
use App\GaelO\UseCases\GetUserRoles\GetUserRolesResponse;
use App\GaelO\Util;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

    public function getUser(int $id=0, GetUserRequest $getUserRequest, GetUserResponse $getUserResponse, GetUser $getUser) {
        $getUserRequest->id = $id;
        $getUser->execute($getUserRequest, $getUserResponse);
        return response()->json($getUserResponse->body)
                ->setStatusCode($getUserResponse->status, $getUserResponse->statusText);
    }

    public function createUser(Request $request, CreateUserRequest $createUserRequest, CreateUserResponse $createUserResponse, CreateUser $createUser) {
        $requestData = $request->all();
        $createUserRequest = Util::fillObject($requestData, $createUserRequest);
        $createUser->execute($createUserRequest, $createUserResponse);
        return response()->json($createUserResponse->body)
                ->setStatusCode($createUserResponse->status, $createUserResponse->statusText);
    }

    public function modifyUser(int $id, Request $request, ModifyUserRequest $modifyUserRequest, ModifyUserResponse $modifyUserResponse, ModifyUser $modifyUser) {
        $requestData = $request->all();
        $requestData['id'] = $id;
        $modifyUserRequest = Util::fillObject($requestData, $modifyUserRequest);
        $modifyUser->execute($modifyUserRequest, $modifyUserResponse);
        return response()->json($modifyUserResponse->body)
                ->setStatusCode($modifyUserResponse->status, $modifyUserResponse->statusText);
    }

    public function changeUserPassword(int $id, Request $request, ChangePasswordRequest $changePasswordRequest, ChangePasswordResponse $changePasswordResponse, ChangePassword $changePassword) {
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

}
