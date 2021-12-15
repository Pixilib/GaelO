<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\AddAffiliatedCenter\AddAffiliatedCenter;
use App\GaelO\UseCases\AddAffiliatedCenter\AddAffiliatedCenterRequest;
use App\GaelO\UseCases\AddAffiliatedCenter\AddAffiliatedCenterResponse;
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
use App\GaelO\UseCases\ForgotPassword\ForgotPassword;
use App\GaelO\UseCases\ForgotPassword\ForgotPasswordRequest;
use App\GaelO\UseCases\ForgotPassword\ForgotPasswordResponse;
use App\GaelO\UseCases\GetAffiliatedCenter\GetAffiliatedCenter;
use App\GaelO\UseCases\GetAffiliatedCenter\GetAffiliatedCenterRequest;
use App\GaelO\UseCases\GetAffiliatedCenter\GetAffiliatedCenterResponse;
use App\GaelO\UseCases\GetRolesInStudyFromUser\GetRolesInStudyFromUser;
use App\GaelO\UseCases\GetRolesInStudyFromUser\GetRolesInStudyFromUserRequest;
use App\GaelO\UseCases\GetRolesInStudyFromUser\GetRolesInStudyFromUserResponse;
use App\GaelO\UseCases\GetStudiesFromUser\GetStudiesFromUser;
use App\GaelO\UseCases\GetStudiesFromUser\GetStudiesFromUserRequest;
use App\GaelO\UseCases\GetStudiesFromUser\GetStudiesFromUserResponse;
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
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class UserController extends Controller
{

    public function forgotPassword(Request $request, ForgotPasswordRequest $forgotPasswordRequest, ForgotPasswordResponse $forgotPasswordResponse, ForgotPassword $resetPassword)
    {
        $requestData = $request->all();
        $requestRequest = Util::fillObject($requestData, $forgotPasswordRequest);
        $resetPassword->execute($requestRequest, $forgotPasswordResponse);
        return $this->getJsonResponse($forgotPasswordResponse->body, $forgotPasswordResponse->status, $forgotPasswordResponse->statusText);
    }

    public function resetPassword(Request $request)
    {

        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
            'password_confirmation' => 'required_with:password|same:password'
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ]);

                $user->save();
            }
        );

        if($status === Password::PASSWORD_RESET) return redirect('/');
        else return response()->noContent(400);

    }

    public function getUser(int $id = null, GetUserRequest $getUserRequest, GetUserResponse $getUserResponse, GetUser $getUser)
    {
        $curentUser = Auth::user();
        $getUserRequest->currentUserId = $curentUser['id'];
        $getUserRequest->id = $id;
        $getUser->execute($getUserRequest, $getUserResponse);
        return $this->getJsonResponse($getUserResponse->body, $getUserResponse->status, $getUserResponse->statusText);
    }

    public function createUser(Request $request, CreateUserRequest $createUserRequest, CreateUserResponse $createUserResponse, CreateUser $createUser)
    {
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
        return $this->getJsonResponse($createUserResponse->body, $createUserResponse->status, $createUserResponse->statusText);
    }

    public function modifyUser(int $id, Request $request, ModifyUserRequest $modifyUserRequest, ModifyUserResponse $modifyUserResponse, ModifyUser $modifyUser)
    {
        $curentUser = Auth::user();
        $modifyUserRequest->currentUserId = $curentUser['id'];
        $requestData = $request->all();
        $requestData['userId'] = $id;
        $modifyUserRequest = Util::fillObject($requestData, $modifyUserRequest);
        $modifyUser->execute($modifyUserRequest, $modifyUserResponse);
        return $this->getJsonResponse($modifyUserResponse->body, $modifyUserResponse->status, $modifyUserResponse->statusText);
    }

    public function modifyUserIdentification(int $id, Request $request, ModifyUserIdentificationRequest $modifyUserRequest, ModifyUserIdentificationResponse $modifyUserResponse, ModifyUserIdentification $modifyUser)
    {
        $curentUser = Auth::user();
        $modifyUserRequest->currentUserId = $curentUser['id'];
        $requestData = $request->all();
        $requestData['userId'] = $id;
        $modifyUserRequest = Util::fillObject($requestData, $modifyUserRequest);
        $modifyUser->execute($modifyUserRequest, $modifyUserResponse);
        return $this->getJsonResponse($modifyUserResponse->body, $modifyUserResponse->status, $modifyUserResponse->statusText);
    }

    public function changeUserPassword(int $id, Request $request, ChangePasswordRequest $changePasswordRequest, ChangePasswordResponse $changePasswordResponse, ChangePassword $changePassword)
    {
        $requestData = $request->all();
        $requestData['id'] = $id;
        $changePasswordRequest = Util::fillObject($requestData, $changePasswordRequest);
        $changePassword->execute($changePasswordRequest, $changePasswordResponse);
        return $this->getJsonResponse($changePasswordResponse->body, $changePasswordResponse->status, $changePasswordResponse->statusText);
    }

    public function deleteUser(int $id, Request $request, DeleteUserRequest $deleteUserRequest, DeleteUserResponse $deleteUserResponse, DeleteUser $deleteUser)
    {
        $user = Auth::user();

        $requestData = get_object_vars($request);
        $deleteUserRequest->id = $id;
        $deleteUserRequest->currentUserId = $user['id'];
        $deleteUserRequest = Util::fillObject($requestData, $deleteUserRequest);
        $deleteUser->execute($deleteUserRequest, $deleteUserResponse);
        return $this->getJsonResponse($deleteUserResponse->body, $deleteUserResponse->status, $deleteUserResponse->statusText);
    }

    public function getRoles(int $id, Request $request, GetRolesInStudyFromUser $getRolesInStudyFromUser, GetRolesInStudyFromUserRequest $getRolesInStudyFromUserRequest, GetRolesInStudyFromUserResponse $getRolesInStudyFromUserResponse)
    {
        $curentUser = Auth::user();
        $queryParam = $request->query();
        $getRolesInStudyFromUserRequest->studyName = $queryParam['studyName'];
        $getRolesInStudyFromUserRequest->currentUserId = $curentUser['id'];
        $getRolesInStudyFromUserRequest->userId = $id;

        $getRolesInStudyFromUser->execute($getRolesInStudyFromUserRequest, $getRolesInStudyFromUserResponse);
        return $this->getJsonResponse($getRolesInStudyFromUserResponse->body, $getRolesInStudyFromUserResponse->status, $getRolesInStudyFromUserResponse->statusText);
    }

    public function getStudiesFromUser(int $userId, GetStudiesFromUser $getStudiesFromUser, GetStudiesFromUserRequest $getStudiesFromUserRequest, GetStudiesFromUserResponse $getStudiesFromUserResponse)
    {
        $curentUser = Auth::user();
        $getStudiesFromUserRequest->currentUserId = $curentUser['id'];
        $getStudiesFromUserRequest->userId = $userId;
        $getStudiesFromUser->execute($getStudiesFromUserRequest, $getStudiesFromUserResponse);

        return $this->getJsonResponse($getStudiesFromUserResponse->body, $getStudiesFromUserResponse->status, $getStudiesFromUserResponse->statusText);
    }

    public function createRole(int $id, Request $request, CreateUserRoles $createUserRole, CreateUserRolesRequest $createUserRoleRequest, CreateUserRolesResponse $createUserRoleResponse)
    {
        $curentUser = Auth::user();
        $requestData = $request->all();
        $queryParam = $request->query();
        $createUserRoleRequest->studyName = $queryParam['studyName'];
        $createUserRoleRequest->userId = $id;
        $createUserRoleRequest->currentUserId = $curentUser['id'];
        $createUserRoleRequest = Util::fillObject($requestData, $createUserRoleRequest);
        $createUserRole->execute($createUserRoleRequest, $createUserRoleResponse);
        return $this->getJsonResponse($createUserRoleResponse->body, $createUserRoleResponse->status, $createUserRoleResponse->statusText);
    }

    public function deleteRole(int $id, Request $request, String $roleName, DeleteUserRole $deleteUserRole, DeleteUserRoleRequest $deleteUserRoleRequest, DeleteUserRoleResponse $deleteUserRoleResponse)
    {
        $curentUser = Auth::user();
        $queryParam = $request->query();
        $deleteUserRoleRequest->studyName = $queryParam['studyName'];
        $deleteUserRoleRequest->currentUserId = $curentUser['id'];
        $deleteUserRoleRequest->userId = $id;
        $deleteUserRoleRequest->role = $roleName;
        $deleteUserRole->execute($deleteUserRoleRequest, $deleteUserRoleResponse);
        return $this->getJsonResponse($deleteUserRoleResponse->body, $deleteUserRoleResponse->status, $deleteUserRoleResponse->statusText);
    }

    public function addAffiliatedCenter(int $userId, Request $request, AddAffiliatedCenter $addAffiliatedCenter, AddAffiliatedCenterRequest $addAffiliatedCenterRequest, AddAffiliatedCenterResponse $addAffiliatedCenterResponse)
    {
        $requestData = $request->all();
        $addAffiliatedCenterRequest = Util::fillObject($requestData, $addAffiliatedCenterRequest);
        $curentUser = Auth::user();
        $addAffiliatedCenterRequest->currentUserId = $curentUser['id'];
        $addAffiliatedCenterRequest->userId = $userId;

        $addAffiliatedCenter->execute($addAffiliatedCenterRequest, $addAffiliatedCenterResponse);

        return $this->getJsonResponse($addAffiliatedCenterResponse->body, $addAffiliatedCenterResponse->status, $addAffiliatedCenterResponse->statusText);
    }

    public function getAffiliatedCenter(int $userId, GetAffiliatedCenter $getAffiliatedCenter, GetAffiliatedCenterRequest $getAffiliatedCenterRequest, GetAffiliatedCenterResponse $getAffiliatedCenterResponse)
    {
        $curentUser = Auth::user();
        $getAffiliatedCenterRequest->currentUserId = $curentUser['id'];
        $getAffiliatedCenterRequest->userId = $userId;
        $getAffiliatedCenter->execute($getAffiliatedCenterRequest, $getAffiliatedCenterResponse);
        return $this->getJsonResponse($getAffiliatedCenterResponse->body, $getAffiliatedCenterResponse->status, $getAffiliatedCenterResponse->statusText);
    }

    public function deleteAffiliatedCenter(int $userId, int $centerCode, DeleteAffiliatedCenter $deleteAffiliatedCenter, DeleteAffiliatedCenterRequest $deleteAffiliatedCenterRequest, DeleteAffiliatedCenterResponse $deleteAffiliatedCenterResponse)
    {
        $curentUser = Auth::user();
        $deleteAffiliatedCenterRequest->currentUserId = $curentUser['id'];
        $deleteAffiliatedCenterRequest->userId = $userId;
        $deleteAffiliatedCenterRequest->centerCode = $centerCode;
        $deleteAffiliatedCenter->execute($deleteAffiliatedCenterRequest, $deleteAffiliatedCenterResponse);
        return $this->getJsonResponse($deleteAffiliatedCenterResponse->body, $deleteAffiliatedCenterResponse->status, $deleteAffiliatedCenterResponse->statusText);
    }

    public function reactivateUser(int $userId, ReactivateUser $reactivateUser, ReactivateUserRequest $reactivateUserRequest, ReactivateUserResponse $reactivateUserResponse)
    {
        $curentUser = Auth::user();
        $reactivateUserRequest->currentUserId = $curentUser['id'];
        $reactivateUserRequest->userId = $userId;
        $reactivateUser->execute($reactivateUserRequest, $reactivateUserResponse);
        return $this->getJsonResponse($reactivateUserResponse->body, $reactivateUserResponse->status, $reactivateUserResponse->statusText);
    }

    public function getUserFromStudy(string $studyName, Request $request, GetUserFromStudyRequest $getUserFromStudyRequest, GetUserFromStudyResponse $getUserFromStudyResponse, GetUserFromStudy $getUserFromStudy)
    {
        $curentUser = Auth::user();
        $queryParam = $request->query();
        $getUserFromStudyRequest->currentUserId = $curentUser['id'];
        $getUserFromStudyRequest->studyName = $studyName;
        $getUserFromStudyRequest->role = $queryParam['role'];
        $getUserFromStudy->execute($getUserFromStudyRequest, $getUserFromStudyResponse);
        return $this->getJsonResponse($getUserFromStudyResponse->body, $getUserFromStudyResponse->status, $getUserFromStudyResponse->statusText);
    }
}
