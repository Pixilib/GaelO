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
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class UserController extends Controller
{

    public function forgotPassword(Request $request, ForgotPasswordRequest $forgotPasswordRequest, ForgotPasswordResponse $forgotPasswordResponse, ForgotPassword $forgotPassword)
    {
        $requestData = $request->all();
        $requestRequest = Util::fillObject($requestData, $forgotPasswordRequest);
        $forgotPassword->execute($requestRequest, $forgotPasswordResponse);
        return $this->getJsonResponse($forgotPasswordResponse->body, $forgotPasswordResponse->status, $forgotPasswordResponse->statusText);
    }

    public function updatePassword(Request $request)
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

                //If password is null, it is the first password definition so we validate email as this reset password is made using email link
                if($user->password === null ){
                    if ($user->markEmailAsVerified()) event(new Verified($user));
                }

                //Reset number of attempts (unblock if blocked)
                $user->attempts = 0;
                $user->save();
            }
        );

        if($status === Password::PASSWORD_RESET) return redirect('/');
        else return response()->noContent(400);

    }

    public function getUser(GetUserRequest $getUserRequest, GetUserResponse $getUserResponse, GetUser $getUser, ?int $id = null)
    {
        $currentUser = Auth::user();
        $getUserRequest->currentUserId = $currentUser['id'];
        $getUserRequest->id = $id;
        $getUser->execute($getUserRequest, $getUserResponse);
        return $this->getJsonResponse($getUserResponse->body, $getUserResponse->status, $getUserResponse->statusText);
    }

    public function createUser(Request $request, CreateUserRequest $createUserRequest, CreateUserResponse $createUserResponse, CreateUser $createUser)
    {
        //Get current user requesting the API
        $currentUser = Auth::user();
        //Add current user ID in Request DTO
        $createUserRequest->currentUserId = $currentUser['id'];
        $requestData = $request->all();
        //Fill DTO with all other request data
        $createUserRequest = Util::fillObject($requestData, $createUserRequest);
        //Execute use case
        $createUser->execute($createUserRequest, $createUserResponse);
        //Output result comming from usecase, here no content has to be shown (only http status code and text)
        return $this->getJsonResponse($createUserResponse->body, $createUserResponse->status, $createUserResponse->statusText);
    }

    public function modifyUser(Request $request, ModifyUserRequest $modifyUserRequest, ModifyUserResponse $modifyUserResponse, ModifyUser $modifyUser, int $id)
    {
        $currentUser = Auth::user();
        $modifyUserRequest->currentUserId = $currentUser['id'];
        $requestData = $request->all();
        $requestData['userId'] = $id;
        $modifyUserRequest = Util::fillObject($requestData, $modifyUserRequest);
        $modifyUser->execute($modifyUserRequest, $modifyUserResponse);
        return $this->getJsonResponse($modifyUserResponse->body, $modifyUserResponse->status, $modifyUserResponse->statusText);
    }

    public function modifyUserIdentification(Request $request, ModifyUserIdentificationRequest $modifyUserRequest, ModifyUserIdentificationResponse $modifyUserResponse, ModifyUserIdentification $modifyUser, int $id)
    {
        $currentUser = Auth::user();
        $modifyUserRequest->currentUserId = $currentUser['id'];
        $requestData = $request->all();
        $requestData['userId'] = $id;
        $modifyUserRequest = Util::fillObject($requestData, $modifyUserRequest);
        $modifyUser->execute($modifyUserRequest, $modifyUserResponse);
        return $this->getJsonResponse($modifyUserResponse->body, $modifyUserResponse->status, $modifyUserResponse->statusText);
    }

    public function deleteUser(Request $request, DeleteUserRequest $deleteUserRequest, DeleteUserResponse $deleteUserResponse, DeleteUser $deleteUser, int $id)
    {
        $user = Auth::user();

        $requestData = get_object_vars($request);
        $deleteUserRequest->id = $id;
        $deleteUserRequest->currentUserId = $user['id'];
        $deleteUserRequest = Util::fillObject($requestData, $deleteUserRequest);
        $deleteUser->execute($deleteUserRequest, $deleteUserResponse);
        return $this->getJsonResponse($deleteUserResponse->body, $deleteUserResponse->status, $deleteUserResponse->statusText);
    }

    public function getRoles(Request $request, GetRolesInStudyFromUser $getRolesInStudyFromUser, GetRolesInStudyFromUserRequest $getRolesInStudyFromUserRequest, GetRolesInStudyFromUserResponse $getRolesInStudyFromUserResponse, int $id)
    {
        $currentUser = Auth::user();
        $queryParam = $request->query();
        $getRolesInStudyFromUserRequest->studyName = $queryParam['studyName'];
        $getRolesInStudyFromUserRequest->currentUserId = $currentUser['id'];
        $getRolesInStudyFromUserRequest->userId = $id;

        $getRolesInStudyFromUser->execute($getRolesInStudyFromUserRequest, $getRolesInStudyFromUserResponse);
        return $this->getJsonResponse($getRolesInStudyFromUserResponse->body, $getRolesInStudyFromUserResponse->status, $getRolesInStudyFromUserResponse->statusText);
    }

    public function getStudiesFromUser(GetStudiesFromUser $getStudiesFromUser, GetStudiesFromUserRequest $getStudiesFromUserRequest, GetStudiesFromUserResponse $getStudiesFromUserResponse, int $userId)
    {
        $currentUser = Auth::user();
        $getStudiesFromUserRequest->currentUserId = $currentUser['id'];
        $getStudiesFromUserRequest->userId = $userId;
        $getStudiesFromUser->execute($getStudiesFromUserRequest, $getStudiesFromUserResponse);

        return $this->getJsonResponse($getStudiesFromUserResponse->body, $getStudiesFromUserResponse->status, $getStudiesFromUserResponse->statusText);
    }

    public function createRole(Request $request, CreateUserRoles $createUserRole, CreateUserRolesRequest $createUserRoleRequest, CreateUserRolesResponse $createUserRoleResponse, int $id)
    {
        $currentUser = Auth::user();
        $requestData = $request->all();
        $queryParam = $request->query();
        $createUserRoleRequest->studyName = $queryParam['studyName'];
        $createUserRoleRequest->userId = $id;
        $createUserRoleRequest->currentUserId = $currentUser['id'];
        $createUserRoleRequest = Util::fillObject($requestData, $createUserRoleRequest);
        $createUserRole->execute($createUserRoleRequest, $createUserRoleResponse);
        return $this->getJsonResponse($createUserRoleResponse->body, $createUserRoleResponse->status, $createUserRoleResponse->statusText);
    }

    public function deleteRole(Request $request, DeleteUserRole $deleteUserRole, DeleteUserRoleRequest $deleteUserRoleRequest, DeleteUserRoleResponse $deleteUserRoleResponse, int $id, String $roleName)
    {
        $currentUser = Auth::user();
        $queryParam = $request->query();
        $deleteUserRoleRequest->studyName = $queryParam['studyName'];
        $deleteUserRoleRequest->currentUserId = $currentUser['id'];
        $deleteUserRoleRequest->userId = $id;
        $deleteUserRoleRequest->role = $roleName;
        $deleteUserRole->execute($deleteUserRoleRequest, $deleteUserRoleResponse);
        return $this->getJsonResponse($deleteUserRoleResponse->body, $deleteUserRoleResponse->status, $deleteUserRoleResponse->statusText);
    }

    public function addAffiliatedCenter(Request $request, AddAffiliatedCenter $addAffiliatedCenter, AddAffiliatedCenterRequest $addAffiliatedCenterRequest, AddAffiliatedCenterResponse $addAffiliatedCenterResponse, int $userId)
    {
        $requestData = $request->all();
        $addAffiliatedCenterRequest = Util::fillObject($requestData, $addAffiliatedCenterRequest);
        $currentUser = Auth::user();
        $addAffiliatedCenterRequest->currentUserId = $currentUser['id'];
        $addAffiliatedCenterRequest->userId = $userId;

        $addAffiliatedCenter->execute($addAffiliatedCenterRequest, $addAffiliatedCenterResponse);

        return $this->getJsonResponse($addAffiliatedCenterResponse->body, $addAffiliatedCenterResponse->status, $addAffiliatedCenterResponse->statusText);
    }

    public function getAffiliatedCenter(GetAffiliatedCenter $getAffiliatedCenter, GetAffiliatedCenterRequest $getAffiliatedCenterRequest, GetAffiliatedCenterResponse $getAffiliatedCenterResponse, int $userId)
    {
        $currentUser = Auth::user();
        $getAffiliatedCenterRequest->currentUserId = $currentUser['id'];
        $getAffiliatedCenterRequest->userId = $userId;
        $getAffiliatedCenter->execute($getAffiliatedCenterRequest, $getAffiliatedCenterResponse);
        return $this->getJsonResponse($getAffiliatedCenterResponse->body, $getAffiliatedCenterResponse->status, $getAffiliatedCenterResponse->statusText);
    }

    public function deleteAffiliatedCenter(DeleteAffiliatedCenter $deleteAffiliatedCenter, DeleteAffiliatedCenterRequest $deleteAffiliatedCenterRequest, DeleteAffiliatedCenterResponse $deleteAffiliatedCenterResponse, int $userId, int $centerCode)
    {
        $currentUser = Auth::user();
        $deleteAffiliatedCenterRequest->currentUserId = $currentUser['id'];
        $deleteAffiliatedCenterRequest->userId = $userId;
        $deleteAffiliatedCenterRequest->centerCode = $centerCode;
        $deleteAffiliatedCenter->execute($deleteAffiliatedCenterRequest, $deleteAffiliatedCenterResponse);
        return $this->getJsonResponse($deleteAffiliatedCenterResponse->body, $deleteAffiliatedCenterResponse->status, $deleteAffiliatedCenterResponse->statusText);
    }

    public function reactivateUser(ReactivateUser $reactivateUser, ReactivateUserRequest $reactivateUserRequest, ReactivateUserResponse $reactivateUserResponse, int $userId)
    {
        $currentUser = Auth::user();
        $reactivateUserRequest->currentUserId = $currentUser['id'];
        $reactivateUserRequest->userId = $userId;
        $reactivateUser->execute($reactivateUserRequest, $reactivateUserResponse);
        return $this->getJsonResponse($reactivateUserResponse->body, $reactivateUserResponse->status, $reactivateUserResponse->statusText);
    }

    public function getUserFromStudy(Request $request, GetUserFromStudyRequest $getUserFromStudyRequest, GetUserFromStudyResponse $getUserFromStudyResponse, GetUserFromStudy $getUserFromStudy, string $studyName)
    {
        $currentUser = Auth::user();
        $queryParam = $request->query();
        $getUserFromStudyRequest->currentUserId = $currentUser['id'];
        $getUserFromStudyRequest->studyName = $studyName;
        $getUserFromStudyRequest->role = $queryParam['role'];
        $getUserFromStudy->execute($getUserFromStudyRequest, $getUserFromStudyResponse);
        return $this->getJsonResponse($getUserFromStudyResponse->body, $getUserFromStudyResponse->status, $getUserFromStudyResponse->statusText);
    }

}
