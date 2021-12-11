<?php

namespace App\GaelO\UseCases\ChangePassword;

use App\GaelO\UseCases\ChangePassword\ChangePasswordRequest;
use App\GaelO\UseCases\ChangePassword\ChangePasswordResponse;
use App\GaelO\Constants\Constants;

use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Adapters\HashInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use Carbon\Carbon;
use Exception;
use Log;

class ChangePassword {

    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private UserRepositoryInterface $userRepositoryInterface;
    private HashInterface $hashInterface;

    public function __construct(UserRepositoryInterface $userRepositoryInterface, TrackerRepositoryInterface $trackerRepositoryInterface, HashInterface $hashInterface){
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->hashInterface = $hashInterface;
     }

    public function execute(ChangePasswordRequest $changeUserPasswordRequest, ChangePasswordResponse $changeUserPasswordResponse) : void {

        try {

            $id = $changeUserPasswordRequest->id;
            $previousPassword = $changeUserPasswordRequest->previous_password;
            $password1 = $changeUserPasswordRequest->password1;
            $password2 = $changeUserPasswordRequest->password2;

            $user = $this->userRepositoryInterface->find($id);

            $dateNow = new \DateTime();
            $validationDate = new \DateTime($user['email_verified_at']);

            if( ( $dateNow->getTimestamp() - $validationDate->getTimestamp() ) > 20*60*1000 ) {
                throw new GaelOForbiddenException('Validation made more that 20 min');
            }

            if($user['status'] !== Constants::USER_STATUS_UNCONFIRMED) {
                $this->checkMatchHashPasswords($previousPassword, $user['password']);
            }

            $this->checkPasswordFormatCorrect($password1);
            $this->checkMatchPasswords($password1, $password2);
            $this->checkNewPassword(
            $password1,
            $user['password'],
            $user['password_previous1'],
            $user['password_previous2']);

            $this->userRepositoryInterface->updateUserPassword($user['id'], $password1);
            $this->userRepositoryInterface->updateUserStatus($user['id'], Constants::USER_STATUS_ACTIVATED);

            $this->trackerRepositoryInterface->writeAction($user['id'], Constants::TRACKER_ROLE_USER, null, null, Constants::TRACKER_CHANGE_PASSWORD, null);

            $changeUserPasswordResponse->status = 200;
            $changeUserPasswordResponse->statusText = 'OK';

        } catch (GaelOException $e) {
            $changeUserPasswordResponse->body = $e->getErrorBody();
            $changeUserPasswordResponse->status = $e->statusCode;
            $changeUserPasswordResponse->statusText = $e->statusText;
        } catch(Exception $e){
            throw $e;
        }

    }

     /**
      * Check that candidate password is not in the 3 last used passwords
      */
    private function checkNewPassword($passwordCandidate, $currentPassword, $previousPassword1, $previousPassword2) : void {
        if ($currentPassword) $checkCurrent = $this->hashInterface->checkHash($passwordCandidate, $currentPassword);
        else $checkCurrent = false;
        if ($previousPassword1) $checkPrevious1 = $this->hashInterface->checkHash($passwordCandidate, $previousPassword1);
        else $checkPrevious1 = false;
        if ($previousPassword2) $checkPrevious2 = $this->hashInterface->checkHash($passwordCandidate, $previousPassword2);
        else $checkPrevious2 = false;

        if( $checkCurrent ||
            $checkPrevious1 ||
            $checkPrevious2 ) {
            throw new GaelOBadRequestException('Already Previously Used Password');
        }
    }

    /**
     * Check Password constraints :
     * Should have length at least 8 characters
     * Should have at least a different case
     * Can have special characters like !@#$%^&*()\[]{}-_+=~`|:;'<>,./?
     */
    private function checkPasswordFormatCorrect(string $password) {
        $checkOneDigit = "(?=.*\d)";
        $checkOneLowerCase = "(?=.*[a-z])";
        $checkOneUpperCase = "(?=.*[A-Z])";
        $checkStrContent = "[0-9A-Za-z\!@#$%^&*()\\[\]{}\-_+=~`|:;'<>,.\/?]"; //Allow for special char
        $checkLength = "{8,}";
        $wholeStringCheck = $checkOneDigit.$checkOneLowerCase.$checkOneUpperCase.$checkStrContent.$checkLength;
        $checkPasswordFormat = preg_match('/^'.$wholeStringCheck.'$/', $password);
        if ($checkPasswordFormat == FALSE){
            throw new GaelOBadRequestException('Password Contraints Failure');
        }
    }

    /**
     * Check password equality, used to check current password and new candidate password
     */
    private function checkMatchPasswords(string $pass1, string $pass2) : void {
        if( $pass1 !== $pass2 ) {
            throw new GaelOBadRequestException('New Passwords Do Not Match');
        }
    }

    private function checkMatchHashPasswords(string $plainTextPassword, string $hashComparator) : void {
        if( !$this->hashInterface->checkHash($plainTextPassword, $hashComparator) ) {
            throw new GaelOBadRequestException('Wrong Previous Password');
        }
    }

}
