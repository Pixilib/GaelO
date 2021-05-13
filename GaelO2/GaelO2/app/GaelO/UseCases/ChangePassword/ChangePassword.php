<?php

namespace App\GaelO\UseCases\ChangePassword;

use App\GaelO\UseCases\ChangePassword\ChangePasswordRequest;
use App\GaelO\UseCases\ChangePassword\ChangePasswordResponse;
use App\GaelO\Constants\Constants;

use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Interfaces\Adapters\HashInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use Exception;

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

            if($user['status'] === Constants::USER_STATUS_UNCONFIRMED) {
                $this->checkMatchHashPasswords($previousPassword, $user['password_temporary']);
            } else {
                $this->checkMatchHashPasswords($previousPassword, $user['password']);
            }

            $this->checkPasswordFormatCorrect($password1);
            $this->checkMatchPasswords($password1, $password2);
            $this->checkNewPassword(
            $password1,
            $user['password_temporary'] ,
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
    private function checkNewPassword($passwordCandidate, $temporaryPassword, $currentPassword, $previousPassword1, $previousPassword2) : void {
        if ($temporaryPassword !==null) $checkTemporary = $this->hashInterface->checkHash($passwordCandidate, $temporaryPassword);
        else $checkTemporary = false;
        if ($currentPassword !== null) $checkCurrent = $this->hashInterface->checkHash($passwordCandidate, $currentPassword);
        else $checkCurrent = false;
        if ($previousPassword1 !== null) $checkPrevious1 = $this->hashInterface->checkHash($passwordCandidate, $previousPassword1);
        else $checkPrevious1 = false;
        if ($previousPassword2) $checkPrevious2 = $this->hashInterface->checkHash($passwordCandidate, $previousPassword2);
        else $checkPrevious2 = false;

        if( $checkTemporary ||
            $checkCurrent ||
            $checkPrevious1 ||
            $checkPrevious2 ) {
            throw new GaelOBadRequestException('Already Previously Used Password');
        }
    }

    /**
     * Check Password constraints :
     * Should have length at least 8 carachters
     * Should not have carachters different from alfa numerical
     * Should have at least a differente case (so strlower should not be equal to original string)
     */
    private function checkPasswordFormatCorrect(string $password) {
        $checkLetterAndNumber =  preg_match('/(?i)([a-z])/', $password) && preg_match('/([0-9])/', $password);
        $checkOnlyAlphaNumerical = !preg_match('/(?i)([^a-z0-9])/', $password);
        $checkNotAllSameCase = (strtolower($password) !== $password);
        $checkLength= strlen($password) >= 8;

        if ( $checkLength === false ||
                $checkLetterAndNumber === false  ||
                $checkOnlyAlphaNumerical === false  ||
                $checkNotAllSameCase === false
            ){
            throw new GaelOBadRequestException('Password Contraints Failure');
        }
    }

    /**
     * Check password equality, used to check current password and new candidate password
     */
    private function checkMatchPasswords(string $pass1, string $pass2) : void {
        if( $pass1 != $pass2 ) {
            throw new GaelOBadRequestException('New Passwords Do Not Match');
        }
    }

    private function checkMatchHashPasswords(string $plainTextPassword, string $hashComparator) : void {
        if( !$this->hashInterface->checkHash($plainTextPassword, $hashComparator) ) {
            throw new GaelOBadRequestException('Wrong Previous Password');
        }
    }

}
